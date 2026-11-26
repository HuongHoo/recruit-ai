<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\CvType;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\VideoCv;
use App\Models\VideoCvAnswer;
use App\Support\PrivateMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Video CV studio: check devices → record answers → profile & CV → preview & share.
 */
class VideoCvController extends Controller
{
    private const STEPS = ['device', 'record', 'info', 'share'];

    public function edit(Request $request): View
    {
        $user = $request->user();
        $videoCv = VideoCv::forUser($user)->load(['answers', 'cv']);

        $step = $request->query('step');
        if (! in_array($step, self::STEPS, true)) {
            $step = $videoCv->hasRecordings() ? 'record' : 'device';
        }

        return view('candidate.video-cv.edit', [
            'videoCv' => $videoCv,
            'step' => $step,
            'config' => config('recruitment.video_cv'),
            'cvs' => $user->cvs()->orderByDesc('is_default')->latest()->get(['id', 'title', 'type', 'is_default']),
            'applicationCount' => $user->applications()->count(),
        ]);
    }

    /**
     * Saves the question list: edits text, removes deleted questions (and their clips), adds new ones.
     */
    public function updateQuestions(Request $request): RedirectResponse
    {
        $videoCv = VideoCv::forUser($request->user());
        $max = config('recruitment.video_cv.max_questions');

        $validated = $request->validate([
            'questions' => ['required', 'array', 'min:1', 'max:'.$max],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.question' => ['required', 'string', 'min:5', 'max:300'],
        ], [
            'questions.max' => "Tối đa {$max} câu hỏi.",
            'questions.*.question.required' => 'Câu hỏi không được để trống.',
            'questions.*.question.min' => 'Câu hỏi quá ngắn.',
        ]);

        DB::transaction(function () use ($videoCv, $validated): void {
            $existing = $videoCv->answers()->get()->keyBy('id');
            $keepIds = collect($validated['questions'])->pluck('id')->filter()->map(fn ($id) => (int) $id);

            $existing->except($keepIds->all())->each->delete();

            // Move kept rows out of the way first so renumbering never hits the unique (video_cv_id, position).
            $videoCv->answers()->update(['position' => DB::raw('position + 100')]);
            $existing = $videoCv->answers()->get()->keyBy('id');

            foreach (array_values($validated['questions']) as $i => $item) {
                $answer = $existing->get((int) ($item['id'] ?? 0)) ?? $videoCv->answers()->make();
                $answer->fill(['position' => $i + 1, 'question' => trim($item['question'])])->save();
            }
        });

        return redirect()->route('candidate.video-cv.edit', ['step' => 'record'])->with('status', 'Đã lưu danh sách câu hỏi.');
    }

    /**
     * Uploads the clip for one question. The previous clip is kept until the new one is saved.
     */
    public function storeAnswer(Request $request, VideoCvAnswer $answer): JsonResponse|RedirectResponse
    {
        $this->ensureOwns($request, $answer);
        $config = config('recruitment.video_cv');

        $validated = $request->validate([
            'video' => ['required', 'file', 'mimetypes:'.implode(',', $config['mimetypes']), 'max:'.$config['max_upload_kb']],
            'duration_seconds' => ['required', 'integer', 'min:2', 'max:'.$config['max_answer_seconds']],
            'source' => ['required', 'in:record,upload'],
        ], [
            'video.mimetypes' => 'Video phải có định dạng '.$config['extensions'].'.',
            'video.max' => 'Video không được lớn hơn '.intdiv($config['max_upload_kb'], 1024).' MB.',
            'duration_seconds.min' => 'Câu trả lời quá ngắn.',
            'duration_seconds.max' => 'Mỗi câu trả lời dài tối đa '.intdiv($config['max_answer_seconds'], 60).' phút.',
        ]);

        $file = $validated['video'];
        $oldPath = $answer->file_path;

        $answer->forceFill([
            'file_path' => $file->store("video-cvs/{$request->user()->id}", 'local'),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'duration_seconds' => $validated['duration_seconds'],
            'source' => $validated['source'],
            'recorded_at' => now(),
        ])->save();

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        $message = "Đã lưu câu trả lời cho câu {$answer->position}.";

        if ($request->expectsJson()) {
            session()->flash('status', $message);

            return response()->json(['redirect' => route('candidate.video-cv.edit', ['step' => 'record'])]);
        }

        return redirect()->route('candidate.video-cv.edit', ['step' => 'record'])->with('status', $message);
    }

    public function destroyAnswer(Request $request, VideoCvAnswer $answer): RedirectResponse
    {
        $this->ensureOwns($request, $answer);

        if ($answer->file_path) {
            Storage::disk('local')->delete($answer->file_path);
        }
        $answer->forceFill(['file_path' => null, 'mime_type' => null, 'file_size' => null, 'duration_seconds' => null, 'source' => null, 'recorded_at' => null])->save();

        return redirect()->route('candidate.video-cv.edit', ['step' => 'record'])->with('status', "Đã xóa câu trả lời của câu {$answer->position}.");
    }

    public function answerFile(Request $request, VideoCvAnswer $answer): BinaryFileResponse
    {
        $this->ensureOwns($request, $answer);

        return PrivateMedia::stream($answer->file_path, $answer->mime_type);
    }

    /**
     * Step 3: profile card shown to employers, plus the CV attached to the Video CV.
     */
    public function updateInfo(Request $request): RedirectResponse
    {
        $user = $request->user();
        $videoCv = VideoCv::forUser($user);
        $config = config('recruitment.video_cv');

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:150'],
            'intro' => ['nullable', 'string', 'max:600'],
            'public_email' => ['nullable', 'email', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$config['max_photo_kb']],
            'remove_photo' => ['nullable', 'boolean'],
            'cv_id' => ['nullable', 'integer', Rule::exists('cvs', 'id')->where('user_id', $user->id)],
            'cv_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:'.config('recruitment.cv.max_upload_kb')],
        ], [
            'photo.max' => 'Ảnh tối đa '.intdiv($config['max_photo_kb'], 1024).' MB.',
            'cv_file.mimes' => 'CV phải là tệp PDF hoặc Word (.doc, .docx).',
            'cv_file.max' => 'CV tối đa '.intdiv(config('recruitment.cv.max_upload_kb'), 1024).' MB.',
        ], [
            'full_name' => 'họ tên',
            'position' => 'vị trí mong muốn',
            'intro' => 'giới thiệu',
            'public_email' => 'email công khai',
            'photo' => 'ảnh đại diện',
        ]);

        $videoCv->fill(Arr::only($validated, ['full_name', 'position', 'intro', 'public_email']));

        if ($request->hasFile('photo')) {
            $oldPhoto = $videoCv->photo_path;
            $videoCv->photo_path = $request->file('photo')->store("video-cvs/{$user->id}/photo", 'local');
        } elseif ($request->boolean('remove_photo')) {
            $oldPhoto = $videoCv->photo_path;
            $videoCv->photo_path = null;
        }

        if ($request->hasFile('cv_file')) {
            $videoCv->cv()->associate($this->storeUploadedCv($request));
        } else {
            $videoCv->cv_id = $validated['cv_id'] ?? null;
        }

        $videoCv->save();

        if (! empty($oldPhoto)) {
            Storage::disk('local')->delete($oldPhoto);
        }

        return redirect()->route('candidate.video-cv.edit', ['step' => 'share'])->with('status', 'Đã lưu thông tin Video CV.');
    }

    /**
     * Step 4 preview: the exact page employers see, served from the candidate's own media routes.
     */
    public function preview(Request $request): View
    {
        $videoCv = VideoCv::forUser($request->user())->load(['answers', 'cv', 'user']);

        return view('video-cv.public', [
            'videoCv' => $videoCv,
            'answers' => $videoCv->recordedAnswers(),
            'photoUrl' => $videoCv->photo_path ? route('candidate.video-cv.photo').'?v='.$videoCv->updated_at->timestamp : null,
            'answerUrl' => fn (VideoCvAnswer $answer) => route('candidate.video-cv.answers.file', $answer).'?v='.$answer->recorded_at?->timestamp,
            'cvUrl' => $videoCv->cv ? route('candidate.cvs.show', $videoCv->cv) : null,
            'isPreview' => true,
            'isOwner' => true,
        ]);
    }

    public function photo(Request $request): BinaryFileResponse
    {
        $videoCv = $request->user()->videoCv()->first();

        return PrivateMedia::stream($videoCv?->photo_path);
    }

    /**
     * Creates (or renews) the share link. Renewing makes the previous link stop working.
     */
    public function share(Request $request): RedirectResponse
    {
        $videoCv = VideoCv::forUser($request->user())->load('answers');

        if ($missing = $videoCv->missingForShare()) {
            throw ValidationException::withMessages(['share' => $missing]);
        }

        $renewing = $videoCv->isShared();
        $videoCv->regenerateShareLink();

        return redirect()->route('candidate.video-cv.edit', ['step' => 'share'])
            ->with('status', $renewing ? 'Đã đổi link chia sẻ. Link cũ không còn xem được nữa.' : 'Đã tạo link chia sẻ Video CV.');
    }

    public function stopSharing(Request $request): RedirectResponse
    {
        $request->user()->videoCv()->first()?->stopSharing();

        return redirect()->route('candidate.video-cv.edit', ['step' => 'share'])->with('status', 'Đã tắt chia sẻ. Link cũ không còn xem được nữa.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->videoCv()->first()?->delete();

        return redirect()->route('video-cv')->with('status', 'Đã xóa Video CV.');
    }

    private function ensureOwns(Request $request, VideoCvAnswer $answer): void
    {
        abort_unless($answer->videoCv()->where('user_id', $request->user()->id)->exists(), 404);
    }

    /**
     * A CV uploaded from the Video CV form is saved to the candidate's CV list like any other upload.
     */
    private function storeUploadedCv(Request $request): Cv
    {
        $user = $request->user();
        $max = config('recruitment.cv.max_per_user');

        if ($user->cvs()->count() >= $max) {
            throw ValidationException::withMessages(['cv_file' => "Bạn đã có {$max} CV. Hãy chọn một CV có sẵn hoặc xóa bớt CV cũ."]);
        }

        $file = $request->file('cv_file');
        $cv = new Cv(['title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)]);
        $cv->type = CvType::Upload;
        $cv->file_path = $file->store("cvs/{$user->id}", 'local');
        $cv->original_name = $file->getClientOriginalName();
        $cv->mime_type = $file->getMimeType();
        $cv->file_size = $file->getSize();
        $cv->is_default = ! $user->cvs()->exists();
        $cv->user()->associate($user);
        $cv->save();

        return $cv;
    }
}
