<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\CvType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\StoreOnlineCvRequest;
use App\Http\Requests\Candidate\UpdateCvRequest;
use App\Http\Requests\Candidate\UploadCvRequest;
use App\Models\Cv;
use App\Models\User;
use App\Support\CvBuilder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvController extends Controller
{
    public function index(Request $request): View
    {
        return view('candidate.cvs.index', [
            'cvs' => $request->user()->cvs()->orderByDesc('is_default')->latest()->get(),
            'maxCvs' => config('recruitment.cv.max_per_user'),
        ]);
    }

    public function create(Request $request): View
    {
        $template = $request->query('template');

        return $this->builder($request, new Cv([
            'title' => 'CV '.$request->user()->name,
            'template' => isset(config('recruitment.cv.templates')[$template]) ? $template : 'ats',
            'accent_color' => '#2563eb',
        ]));
    }

    public function store(StoreOnlineCvRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanAddCv($user);

        $cv = new Cv;
        $cv->type = CvType::Online;
        $cv->user()->associate($user);
        $cv->is_default = ! $user->cvs()->exists();
        CvBuilder::save($user, $cv, $request->validated());

        return redirect()->route('candidate.cvs.show', $cv)->with('status', 'Đã tạo CV. Bạn có thể xem trước và tải xuống PDF.');
    }

    /**
     * Live preview for the builder: renders the chosen template from unsaved form data.
     */
    public function preview(Request $request): View
    {
        $data = CvBuilder::previewData($request->user(), $request->all());

        return view('candidate.cvs.templates.'.$data['cv']->template, $data);
    }

    public function upload(UploadCvRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanAddCv($user);

        $file = $request->file('file');

        $cv = new Cv(['title' => $request->validated('title') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)]);
        $cv->type = CvType::Upload;
        $cv->file_path = $file->store("cvs/{$user->id}", 'local');
        $cv->original_name = $file->getClientOriginalName();
        $cv->mime_type = $file->getMimeType();
        $cv->file_size = $file->getSize();
        $this->saveForUser($user, $cv);

        if ($jobId = $request->validated('return_to_job')) {
            return redirect()->to(route('jobs.show', $jobId).'#ho-tro-ung-tuyen')
                ->with('status', 'Đã tải CV lên thành công.')
                ->with('support_tab', $request->validated('support_tab') ?? 'match')
                ->with('selected_cv_id', $cv->id);
        }

        return redirect()->route('candidate.cvs.index')->with('status', 'Đã tải CV lên thành công.');
    }

    /**
     * Online CV: printable preview. Uploaded PDF: shown inline in the browser.
     */
    public function show(Cv $cv): View|StreamedResponse
    {
        Gate::authorize('view', $cv);

        if (! $cv->isOnline()) {
            return $this->cvDisk()->response($cv->file_path, $cv->original_name, [
                'Content-Disposition' => ($cv->isPdf() ? 'inline' : 'attachment').'; filename="'.addslashes($cv->original_name).'"',
            ]);
        }

        $user = $cv->user;

        return view('candidate.cvs.show', [
            'cv' => $cv,
            'user' => $user,
            'profile' => $user->profile(),
            'educations' => $user->educations()->get(),
            'experiences' => $user->experiences()->get(),
        ]);
    }

    public function download(Cv $cv): StreamedResponse|RedirectResponse
    {
        Gate::authorize('view', $cv);

        if ($cv->isOnline()) {
            return redirect()->route('candidate.cvs.show', ['cv' => $cv, 'print' => 1]);
        }

        return $this->cvDisk()->download($cv->file_path, $cv->original_name);
    }

    public function edit(Request $request, Cv $cv): View
    {
        Gate::authorize('update', $cv);

        return $cv->isOnline() ? $this->builder($request, $cv) : view('candidate.cvs.rename', ['cv' => $cv]);
    }

    public function update(UpdateCvRequest $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('update', $cv);

        if ($cv->isOnline()) {
            CvBuilder::save($request->user(), $cv, $request->validated());
        } else {
            $cv->update($request->validated());
        }

        return redirect()->route('candidate.cvs.index')->with('status', 'Đã cập nhật CV.');
    }

    /**
     * "Đưa CV có sẵn vào mẫu": switches an online CV to another template, keeping its content.
     */
    public function applyTemplate(Request $request, Cv $cv): RedirectResponse
    {
        Gate::authorize('update', $cv);

        $validated = $request->validate([
            'template' => ['required', Rule::in(array_keys(config('recruitment.cv.templates')))],
        ]);

        if (! $cv->isOnline()) {
            return back()->withErrors(['template' => 'CV tải lên là file có sẵn nên không đổi mẫu được. Hãy tạo CV trực tuyến theo mẫu này.']);
        }

        $cv->update(['template' => $validated['template']]);

        return redirect()->route('candidate.cvs.show', $cv)
            ->with('status', 'Đã chuyển "'.$cv->title.'" sang mẫu '.$cv->templateName().'.');
    }

    public function makeDefault(Cv $cv): RedirectResponse
    {
        Gate::authorize('update', $cv);

        DB::transaction(function () use ($cv): void {
            $cv->user->cvs()->whereKeyNot($cv->id)->update(['is_default' => false]);
            $cv->forceFill(['is_default' => true])->save();
        });

        return back()->with('status', "Đã đặt \"{$cv->title}\" làm CV mặc định.");
    }

    public function destroy(Cv $cv): RedirectResponse
    {
        Gate::authorize('delete', $cv);

        DB::transaction(function () use ($cv): void {
            $user = $cv->user;
            $wasDefault = $cv->is_default;
            $cv->delete();

            if ($wasDefault) {
                $user->cvs()->latest()->first()?->forceFill(['is_default' => true])->save();
            }
        });

        return redirect()->route('candidate.cvs.index')->with('status', 'Đã xóa CV.');
    }

    private function builder(Request $request, Cv $cv): View
    {
        return view('candidate.cvs.create', [
            'cv' => $cv,
            'form' => CvBuilder::formValues($request->user()),
            'email' => $request->user()->email,
        ]);
    }

    /**
     * Typed as the concrete adapter so response()/download() are known to static analysis.
     */
    private function cvDisk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter */
        return Storage::disk('local');
    }

    private function ensureCanAddCv(User $user): void
    {
        $max = config('recruitment.cv.max_per_user');

        if ($user->cvs()->count() >= $max) {
            throw ValidationException::withMessages(['file' => "Bạn chỉ có thể lưu tối đa {$max} CV. Hãy xóa bớt CV cũ."]);
        }
    }

    /**
     * The first CV a candidate creates automatically becomes the default one.
     */
    private function saveForUser(User $user, Cv $cv): void
    {
        $cv->user()->associate($user);
        $cv->is_default = ! $user->cvs()->exists();
        $cv->save();
    }
}
