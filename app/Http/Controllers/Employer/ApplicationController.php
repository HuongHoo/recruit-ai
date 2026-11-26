<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CvMatchAnalysis;
use App\Models\VideoCvAnswer;
use App\Notifications\ApplicationStatusChanged;
use App\Support\PrivateMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    /**
     * Applicant pool ("Kho ứng viên"): CV search with keyword rules plus profile filters.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'must' => ['nullable', 'array', 'max:10'],
            'must.*' => ['string', 'max:50'],
            'any' => ['nullable', 'array', 'max:10'],
            'any.*' => ['string', 'max:50'],
            'not' => ['nullable', 'array', 'max:10'],
            'not.*' => ['string', 'max:50'],
            'city' => ['nullable', 'string', 'max:60'],
            'gender' => ['nullable', Rule::in(array_keys(config('recruitment.genders')))],
            'degree' => ['nullable', 'string', 'max:60'],
            'job' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(ApplicationStatus::class)],
            'per_page' => ['nullable', Rule::in([10, 20, 50])],
        ]);

        $applications = Application::query()
            ->whereRelation('jobPost', 'user_id', $user->id)
            ->when($filters['q'] ?? null, fn ($q, string $term) => $this->containing($q, $term))
            ->when($filters['must'] ?? [], function ($q, array $terms) {
                foreach ($terms as $term) {
                    $this->containing($q, $term);
                }
            })
            ->when($filters['any'] ?? [], fn ($q, array $terms) => $q->where(function ($group) use ($terms) {
                foreach ($terms as $term) {
                    $group->orWhere(fn ($one) => $this->containing($one, $term));
                }
            }))
            ->when($filters['not'] ?? [], function ($q, array $terms) {
                foreach ($terms as $term) {
                    $q->whereNot(fn ($one) => $this->containing($one, $term));
                }
            })
            ->when($filters['city'] ?? null, fn ($q, string $city) => $q->whereHas('candidate.candidateProfile', fn ($p) => $p
                ->where('address', 'like', "%{$city}%")->orWhere('desired_locations', 'like', "%{$city}%")))
            ->when($filters['gender'] ?? null, fn ($q, string $gender) => $q->whereRelation('candidate.candidateProfile', 'gender', $gender))
            ->when($filters['degree'] ?? null, fn ($q, string $degree) => $q->whereRelation('candidate.educations', 'degree', $degree))
            ->when($filters['job'] ?? null, fn ($q, int $job) => $q->where('job_post_id', $job))
            ->when($filters['status'] ?? null, fn ($q, string $status) => $q->where('status', $status))
            ->with(['candidate.candidateProfile', 'jobPost'])
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 20))->withQueryString();

        // AI match scores the candidates generated for these jobs, keyed "userId-jobId".
        $matchScores = CvMatchAnalysis::query()
            ->whereIn('user_id', $applications->pluck('user_id'))
            ->whereIn('job_post_id', $applications->pluck('job_post_id'))
            ->get(['user_id', 'job_post_id', 'score'])
            ->mapWithKeys(fn (CvMatchAnalysis $m) => ["{$m->user_id}-{$m->job_post_id}" => $m->score]);

        return view('employer.applications.index', [
            'applications' => $applications,
            'matchScores' => $matchScores,
            'jobs' => $user->jobPosts()->latest()->get(['id', 'title']),
            'filters' => $filters,
        ]);
    }

    /**
     * Limits applications to those whose CV data (profile, experience, education, CV title) contains the term.
     *
     * @param  Builder<Application>  $query
     */
    private function containing($query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_').'%';

        $query->where(fn ($q) => $q
            ->where('cv_title', 'like', $like)
            ->orWhereHas('candidate', fn ($c) => $c
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhereHas('candidateProfile', fn ($p) => $p->where(fn ($f) => $f
                    ->where('headline', 'like', $like)->orWhere('summary', 'like', $like)
                    ->orWhere('skills', 'like', $like)->orWhere('address', 'like', $like)))
                ->orWhereHas('experiences', fn ($e) => $e->where(fn ($f) => $f
                    ->where('position', 'like', $like)->orWhere('company', 'like', $like)->orWhere('description', 'like', $like)))
                ->orWhereHas('educations', fn ($e) => $e->where(fn ($f) => $f
                    ->where('school', 'like', $like)->orWhere('major', 'like', $like)))));
    }

    /**
     * Opening a new application marks it as "viewed" so the candidate can see it was read.
     */
    public function show(Application $application): View
    {
        Gate::authorize('update', $application);

        if ($application->status === ApplicationStatus::Submitted) {
            $application->moveTo(ApplicationStatus::Viewed);
        }

        $candidate = $application->candidate;

        return view('employer.applications.show', [
            'application' => $application->load(['jobPost', 'cv']),
            'candidate' => $candidate,
            'profile' => $candidate->profile(),
            'videoCv' => $candidate->videoCv()->with('answers')->first(),
            'statuses' => ApplicationStatus::employerSettable(),
        ]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        Gate::authorize('update', $application);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ApplicationStatus::class)->only(ApplicationStatus::employerSettable())],
        ]);

        $status = ApplicationStatus::from($validated['status']);

        if ($application->moveTo($status) && ApplicationStatusChanged::shouldNotify($status)) {
            $application->candidate->notify(new ApplicationStatusChanged($application));
        }

        return back()->with('status', "Đã chuyển trạng thái hồ sơ sang \"{$status->label()}\".");
    }

    /**
     * Streams one answer of the applicant's Video CV; only employers who received the application get here.
     */
    public function video(Application $application, VideoCvAnswer $answer): BinaryFileResponse
    {
        Gate::authorize('update', $application);
        abort_unless($answer->videoCv?->user_id === $application->user_id, 404);

        return PrivateMedia::stream($answer->file_path, $answer->mime_type);
    }

    public function videoPhoto(Application $application): BinaryFileResponse
    {
        Gate::authorize('update', $application);

        return PrivateMedia::stream($application->candidate->videoCv?->photo_path);
    }

    /**
     * Shows the CV attached to the application (online CV preview or the uploaded file).
     */
    public function cv(Application $application): View|StreamedResponse
    {
        Gate::authorize('update', $application);

        $cv = $application->cv;
        abort_if($cv === null, 404, 'Ứng viên đã xóa CV này.');

        if (! $cv->isOnline()) {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('local');

            return $disk->response($cv->file_path, $cv->original_name, [
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
            'backUrl' => route('employer.applications.show', $application),
        ]);
    }
}
