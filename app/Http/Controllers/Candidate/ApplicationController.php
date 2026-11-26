<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\ApplyJobRequest;
use App\Models\Application;
use App\Models\JobPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['status' => ['nullable', Rule::enum(ApplicationStatus::class)]]);

        $applications = $request->user()->applications();

        return view('candidate.applications.index', [
            'applications' => (clone $applications)->with(['jobPost', 'interviews' => fn ($q) => $q->where('status', 'scheduled')->where('scheduled_at', '>=', now())])
                ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
            'statusCounts' => (clone $applications)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'currentStatus' => $request->query('status'),
        ]);
    }

    public function store(ApplyJobRequest $request, JobPost $job): RedirectResponse
    {
        Gate::authorize('apply', $job);

        $user = $request->user();

        if ($user->applications()->where('job_post_id', $job->id)->exists()) {
            return back()->withErrors(['cv_id' => 'Bạn đã ứng tuyển vị trí này rồi.']);
        }

        $cv = $user->cvs()->findOrFail($request->validated('cv_id'));

        $application = new Application($request->safe()->only('cover_letter'));
        $application->jobPost()->associate($job);
        $application->candidate()->associate($user);
        $application->cv()->associate($cv);
        $application->cv_title = $cv->title;
        $application->save();

        return redirect()->route('candidate.applications.index')->with('status', "Đã nộp hồ sơ ứng tuyển \"{$job->title}\".");
    }
}
