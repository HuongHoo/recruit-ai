<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employer\InterviewRequest;
use App\Models\Application;
use App\Models\Interview;
use App\Notifications\InterviewScheduled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InterviewController extends Controller
{
    /**
     * Interview overview for a date range, as a list or a month calendar.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['list', 'calendar'])],
            'range' => ['nullable', Rule::in(['today', 'week', 'month', 'next30', 'custom'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'interviewer' => ['nullable', 'string', 'max:120'],
            'application' => ['nullable', 'integer'],
        ]);

        $view = $filters['view'] ?? 'list';
        $range = $filters['range'] ?? (isset($filters['from']) ? 'custom' : 'next30');
        [$from, $to] = match ($range) {
            'today' => [today(), today()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => [Carbon::parse($filters['from'] ?? today())->startOfDay(), Carbon::parse($filters['to'] ?? $filters['from'] ?? today())->endOfDay()],
            default => [today(), today()->addDays(30)->endOfDay()],
        };

        // The calendar always shows whole weeks of the month the range starts in.
        $month = $from->copy()->startOfMonth();
        $gridStart = $month->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $owned = fn () => Interview::query()->ownedBy($user)
            ->when($filters['interviewer'] ?? null, fn ($q, string $name) => $q->where('interviewer', $name));

        $interviews = $owned()->with(['application.candidate', 'application.jobPost'])
            ->whereBetween('scheduled_at', $view === 'calendar' ? [$gridStart, $gridEnd->copy()->endOfDay()] : [$from, $to])
            ->orderBy('scheduled_at')->get();

        $active = fn () => $owned()->where('status', '!=', InterviewStatus::Cancelled);

        return view('employer.interviews.index', [
            'view' => $view,
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'month' => $month,
            'gridStart' => $gridStart,
            'gridEnd' => $gridEnd,
            'interviews' => $interviews,
            'byDay' => $interviews->groupBy(fn (Interview $i) => $i->scheduled_at->toDateString()),
            'filters' => $filters,
            'stats' => [
                'total' => $active()->whereBetween('scheduled_at', [$from, $to])->count(),
                'today' => $active()->whereDate('scheduled_at', today())->count(),
                'week' => $active()->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'month' => $active()->whereBetween('scheduled_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ],
            'interviewers' => Interview::query()->ownedBy($user)->whereNotNull('interviewer')->distinct()->orderBy('interviewer')->pluck('interviewer'),
            'applications' => Application::query()
                ->whereRelation('jobPost', 'user_id', $user->id)
                ->whereNotIn('status', [ApplicationStatus::Rejected])
                ->with(['candidate', 'jobPost'])->latest()->get(),
            'preselect' => $filters['application'] ?? null,
            'types' => InterviewType::cases(),
        ]);
    }

    public function store(InterviewRequest $request): RedirectResponse
    {
        $application = Application::query()->findOrFail($request->integer('application_id'));
        abort_unless($request->user()->can('update', $application), 403);

        $interview = $application->interviews()->create($request->safe()->except('application_id'));

        if (in_array($application->status, [ApplicationStatus::Submitted, ApplicationStatus::Viewed], true)) {
            $application->moveTo(ApplicationStatus::Interview);
        }

        $application->candidate->notify(new InterviewScheduled($interview));

        return $this->backToMonth($interview, 'Đã lên lịch phỏng vấn và gửi thông báo cho ứng viên.');
    }

    public function update(InterviewRequest $request, Interview $interview): RedirectResponse
    {
        $this->authorizeOwner($request, $interview);

        $interview->fill($request->validated());
        $changed = $interview->isDirty(['scheduled_at', 'type', 'location']);
        $interview->status = InterviewStatus::Scheduled;
        $interview->save();

        if ($changed) {
            $interview->application->candidate->notify(new InterviewScheduled($interview, 'rescheduled'));
        }

        return $this->backToMonth($interview, 'Đã cập nhật lịch phỏng vấn.');
    }

    public function status(Request $request, Interview $interview): RedirectResponse
    {
        $this->authorizeOwner($request, $interview);
        $validated = $request->validate(['status' => ['required', Rule::enum(InterviewStatus::class)->only([InterviewStatus::Completed, InterviewStatus::Cancelled])]]);

        $status = InterviewStatus::from($validated['status']);
        $wasScheduled = $interview->status === InterviewStatus::Scheduled;
        $interview->status = $status;
        $interview->save();

        if ($status === InterviewStatus::Cancelled && $wasScheduled && $interview->scheduled_at->isFuture()) {
            $interview->application->candidate->notify(new InterviewScheduled($interview, 'cancelled'));
        }

        return $this->backToMonth($interview, $status === InterviewStatus::Completed ? 'Đã đánh dấu buổi phỏng vấn hoàn thành.' : 'Đã hủy lịch phỏng vấn.');
    }

    public function destroy(Request $request, Interview $interview): RedirectResponse
    {
        $this->authorizeOwner($request, $interview);
        $interview->delete();

        return $this->backToMonth($interview, 'Đã xóa lịch phỏng vấn.');
    }

    private function authorizeOwner(Request $request, Interview $interview): void
    {
        abort_unless($request->user()->can('update', $interview->application), 404);
    }

    private function backToMonth(Interview $interview, string $message): RedirectResponse
    {
        $at = $interview->scheduled_at;
        $day = $at->format('Y-m-d');
        $params = $at->between(today(), today()->addDays(30)->endOfDay()) ? [] : ['range' => 'custom', 'from' => $day, 'to' => $day];

        return redirect()->route('employer.interviews.index', $params)->with('status', $message);
    }
}
