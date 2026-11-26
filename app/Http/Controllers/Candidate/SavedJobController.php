<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedJobController extends Controller
{
    public function index(Request $request): View
    {
        return view('candidate.saved-jobs.index', [
            'jobs' => $request->user()->savedJobs()->orderByPivot('created_at', 'desc')->paginate(config('recruitment.jobs_per_page')),
        ]);
    }

    public function store(Request $request, JobPost $job): RedirectResponse
    {
        abort_unless($job->isVisible(), 404);

        $request->user()->savedJobs()->syncWithoutDetaching([$job->id]);

        return back()->with('status', "Đã lưu \"{$job->title}\" vào việc làm yêu thích.");
    }

    public function destroy(Request $request, JobPost $job): RedirectResponse
    {
        $request->user()->savedJobs()->detach($job->id);

        return back()->with('status', "Đã bỏ lưu \"{$job->title}\".");
    }
}
