<?php

namespace App\Http\Controllers;

use App\Enums\JobType;
use App\Models\CvMatchAnalysis;
use App\Models\JobPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public job search and job detail pages.
 */
class JobController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['keyword', 'category', 'location', 'job_type']);
        $user = $request->user();

        return view('jobs.index', [
            'jobs' => JobPost::query()->visible()->filter($filters)->promotedFirst()
                ->paginate(config('recruitment.jobs_per_page'))->withQueryString(),
            'filters' => $filters,
            'jobTypes' => JobType::cases(),
            'savedJobIds' => $user?->isCandidate() ? $user->savedJobs()->pluck('job_posts.id')->all() : [],
        ]);
    }

    public function show(Request $request, JobPost $job): View
    {
        $user = $request->user();
        $isOwner = $user?->id === $job->user_id;

        abort_unless($job->isVisible() || $isOwner, 404);

        $isCandidate = (bool) $user?->isCandidate();

        return view('jobs.show', [
            'job' => $job,
            'isCandidate' => $isCandidate,
            'isSaved' => $isCandidate && $user->savedJobs()->whereKey($job->id)->exists(),
            'application' => $isCandidate ? $user->applications()->where('job_post_id', $job->id)->first() : null,
            'cvs' => $isCandidate ? $user->cvs()->orderByDesc('is_default')->latest()->get() : collect(),
            'matchScores' => $isCandidate
                ? CvMatchAnalysis::query()->where('user_id', $user->id)->where('job_post_id', $job->id)->pluck('score', 'cv_id')
                : collect(),
            'relatedJobs' => JobPost::query()->visible()->whereKeyNot($job->id)->where('category', $job->category)->promotedFirst()->limit(4)->get(),
        ]);
    }
}
