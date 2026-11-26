<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\RequirementMatch;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvMatchAnalysis;
use App\Models\JobPost;
use App\Notifications\CvMatchCompleted;
use App\Services\AiServiceException;
use App\Services\CvMatchAnalyzer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Độ phù hợp giữa CV và JD": AI analysis of one of the candidate's CVs against a job.
 */
class CvMatchController extends Controller
{
    public function __construct(private CvMatchAnalyzer $analyzer) {}

    public function show(Request $request, JobPost $job): View
    {
        $this->ensureJobAvailable($request, $job);
        $cv = $this->ownedCv($request);

        $analysis = CvMatchAnalysis::query()->where('cv_id', $cv->id)->where('job_post_id', $job->id)->first();

        return view('candidate.cv-match.show', [
            'job' => $job,
            'cv' => $cv,
            'cvs' => $request->user()->cvs()->orderByDesc('is_default')->latest()->get(),
            'analysis' => $analysis,
            'isOutdated' => $analysis !== null && $this->analyzer->isOutdated($analysis->setRelations(['cv' => $cv, 'jobPost' => $job])),
            'isSupported' => $this->analyzer->supports($cv),
            'statuses' => RequirementMatch::cases(),
        ]);
    }

    public function store(Request $request, JobPost $job): RedirectResponse
    {
        $this->ensureJobAvailable($request, $job);
        $cv = $this->ownedCv($request);

        try {
            $analysis = $this->analyzer->analyze($cv, $job, $request->boolean('refresh'));
        } catch (AiServiceException $exception) {
            return redirect()->route('candidate.cv-match.show', [$job, 'cv_id' => $cv->id])->withErrors(['analysis' => $exception->getMessage()]);
        }

        // Only a fresh AI result is news; a cached analysis returned as-is is not.
        if ($analysis->wasRecentlyCreated || $analysis->wasChanged()) {
            $request->user()->notify(new CvMatchCompleted($analysis, $job));
        }

        return redirect()->route('candidate.cv-match.show', [$job, 'cv_id' => $cv->id]);
    }

    /**
     * Visible jobs, or jobs the candidate already applied to (so they can still review the match).
     */
    private function ensureJobAvailable(Request $request, JobPost $job): void
    {
        abort_unless(
            $job->isVisible() || $request->user()->applications()->where('job_post_id', $job->id)->exists(),
            404,
        );
    }

    private function ownedCv(Request $request): Cv
    {
        $request->validate(['cv_id' => ['required', 'integer']]);

        return $request->user()->cvs()->findOrFail($request->integer('cv_id'));
    }
}
