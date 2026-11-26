<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use App\Models\MockInterview;
use App\Services\AiServiceException;
use App\Services\InterviewVoice;
use App\Services\MockInterviewCoach;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * "Phỏng vấn mô phỏng với AI": device check → spoken/typed Q&A → AI evaluation.
 */
class MockInterviewController extends Controller
{
    public function __construct(private MockInterviewCoach $coach) {}

    public function index(Request $request): View
    {
        return view('candidate.mock-interviews.index', [
            'interviews' => $request->user()->mockInterviews()->with('jobPost')->latest()->paginate(10),
        ]);
    }

    public function store(Request $request, JobPost $job): RedirectResponse
    {
        abort_unless(
            $job->isVisible() || $request->user()->applications()->where('job_post_id', $job->id)->exists(),
            404,
        );

        $validated = $request->validate([
            'cv_id' => ['required', 'integer'],
            'language' => ['required', Rule::in(array_keys(config('recruitment.mock_interview.languages')))],
        ]);

        $cv = $request->user()->cvs()->findOrFail($validated['cv_id']);

        try {
            $interview = $this->coach->start($cv, $job, $validated['language']);
        } catch (AiServiceException $exception) {
            return redirect()->to(route('jobs.show', $job).'#ho-tro-ung-tuyen')
                ->withInput()
                ->with('support_tab', 'interview')
                ->withErrors(['interview' => $exception->getMessage()]);
        }

        return redirect()->route('candidate.mock-interviews.setup', $interview);
    }

    /**
     * Camera / microphone check before the interview starts.
     */
    public function setup(MockInterview $mockInterview): View|RedirectResponse
    {
        Gate::authorize('view', $mockInterview);

        if ($mockInterview->isCompleted()) {
            return redirect()->route('candidate.mock-interviews.show', $mockInterview);
        }

        return view('candidate.mock-interviews.setup', [
            'interview' => $mockInterview,
            'job' => $mockInterview->jobPost,
            'testPhrase' => config("recruitment.mock_interview.languages.{$mockInterview->language}.test_phrase"),
        ]);
    }

    public function session(Request $request, MockInterview $mockInterview): View|RedirectResponse
    {
        Gate::authorize('view', $mockInterview);

        if ($mockInterview->isCompleted()) {
            return redirect()->route('candidate.mock-interviews.show', $mockInterview);
        }

        return view('candidate.mock-interviews.session', [
            'interview' => $mockInterview,
            'job' => $mockInterview->jobPost,
            'interviewer' => $mockInterview->interviewer((string) $request->query('interviewer', '')),
        ]);
    }

    /**
     * One line of the interviewer, read aloud by the AI voice (WAV). Generated once, then served from disk.
     */
    public function speech(Request $request, MockInterview $mockInterview, string $line, InterviewVoice $voice): BinaryFileResponse|JsonResponse
    {
        Gate::authorize('view', $mockInterview);

        $interviewer = $mockInterview->interviewer((string) $request->query('interviewer', ''));
        $text = $mockInterview->speechLine($interviewer, $line);
        abort_if(blank($text), 404);

        try {
            $path = $voice->clip($text, $interviewer['ttsVoice']);
        } catch (AiServiceException|LockTimeoutException) {
            return response()->json(['message' => 'Giọng đọc AI đang bận, vui lòng thử lại sau.'], 503, ['Retry-After' => '20']);
        }

        return response()->file($path, ['Content-Type' => 'audio/wav', 'Cache-Control' => 'private, max-age=604800']);
    }

    /**
     * Autosaves one answer while the candidate is speaking/typing, so a reload does not lose it.
     */
    public function answer(Request $request, MockInterview $mockInterview): Response
    {
        Gate::authorize('update', $mockInterview);
        abort_if($mockInterview->isCompleted(), 409);

        $validated = $request->validate([
            'index' => ['required', 'integer', 'min:0', 'max:'.(count($mockInterview->questions) - 1)],
            'answer' => ['nullable', 'string', 'max:'.config('recruitment.mock_interview.max_answer_chars')],
        ]);

        $answers = $mockInterview->answers;
        $answers[$validated['index']] = trim($validated['answer'] ?? '');
        $mockInterview->update(['answers' => $answers]);

        return response()->noContent();
    }

    public function finish(Request $request, MockInterview $mockInterview): RedirectResponse
    {
        Gate::authorize('update', $mockInterview);

        if ($mockInterview->isCompleted()) {
            return redirect()->route('candidate.mock-interviews.show', $mockInterview);
        }

        // The page also posts every answer, in case an autosave request was lost.
        $validated = $request->validate([
            'answers' => ['nullable', 'array', 'max:'.count($mockInterview->questions)],
            'answers.*' => ['nullable', 'string', 'max:'.config('recruitment.mock_interview.max_answer_chars')],
        ]);

        $answers = $mockInterview->answers;

        foreach ($validated['answers'] ?? [] as $index => $answer) {
            if (array_key_exists($index, $answers)) {
                $answers[$index] = trim($answer ?? '');
            }
        }

        $mockInterview->update(['answers' => $answers]);

        try {
            $this->coach->evaluate($mockInterview);
        } catch (AiServiceException $exception) {
            return redirect()->route('candidate.mock-interviews.session', $mockInterview)
                ->withErrors(['interview' => $exception->getMessage()]);
        }

        return redirect()->route('candidate.mock-interviews.show', $mockInterview);
    }

    /**
     * The AI evaluation.
     */
    public function show(Request $request, MockInterview $mockInterview): View|RedirectResponse
    {
        Gate::authorize('view', $mockInterview);

        if (! $mockInterview->isCompleted()) {
            return redirect()->route('candidate.mock-interviews.session', $mockInterview);
        }

        return view('candidate.mock-interviews.show', [
            'interview' => $mockInterview,
            'job' => $mockInterview->jobPost,
            'canRetry' => $mockInterview->cv_id !== null && $mockInterview->jobPost->isVisible(),
        ]);
    }
}
