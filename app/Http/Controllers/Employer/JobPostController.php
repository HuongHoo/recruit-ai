<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Enums\JobPostStatus;
use App\Enums\JobType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employer\JobPostRequest;
use App\Models\JobPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobPostController extends Controller
{
    public function index(Request $request): View
    {
        return view('employer.jobs.index', [
            'jobs' => $request->user()->jobPosts()
                ->withCount(['applications', 'applications as new_applications_count' => fn ($q) => $q->where('status', ApplicationStatus::Submitted)])
                ->latest()->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $lastJob = $request->user()->jobPosts()->latest()->first();

        return $this->form(new JobPost([
            'company_name' => $lastJob?->company_name ?? $request->user()->company?->name,
            'job_type' => JobType::FullTime,
            'status' => JobPostStatus::Open,
        ]));
    }

    public function store(JobPostRequest $request): RedirectResponse
    {
        $job = $request->user()->jobPosts()->create($request->validated());

        return redirect()->route('employer.jobs.show', $job)->with('status', 'Đã đăng tin tuyển dụng.');
    }

    /**
     * Applicants for one job, filterable by status.
     */
    public function show(Request $request, JobPost $job): View
    {
        Gate::authorize('update', $job);
        $request->validate(['status' => ['nullable', Rule::enum(ApplicationStatus::class)]]);

        return view('employer.jobs.show', [
            'job' => $job,
            'applications' => $job->applications()->with('candidate')
                ->when($request->query('status'), fn ($q, string $status) => $q->where('status', $status))
                ->latest()->paginate(20)->withQueryString(),
            'statusCounts' => $job->applications()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'currentStatus' => $request->query('status'),
        ]);
    }

    public function edit(JobPost $job): View
    {
        Gate::authorize('update', $job);

        return $this->form($job);
    }

    public function update(JobPostRequest $request, JobPost $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $job->update($request->validated());

        return redirect()->route('employer.jobs.index')->with('status', 'Đã cập nhật tin tuyển dụng.');
    }

    public function destroy(JobPost $job): RedirectResponse
    {
        Gate::authorize('delete', $job);

        $job->delete();

        return redirect()->route('employer.jobs.index')->with('status', 'Đã xóa tin tuyển dụng.');
    }

    private function form(JobPost $job): View
    {
        return view('employer.jobs.form', [
            'job' => $job,
            'jobTypes' => JobType::cases(),
            'statuses' => JobPostStatus::cases(),
        ]);
    }
}
