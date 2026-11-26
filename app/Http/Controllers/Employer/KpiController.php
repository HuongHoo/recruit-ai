<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Recruitment progress per team member for a week or a month.
 */
class KpiController extends Controller
{
    /** Metric key => [label, dot colour]. */
    public const METRICS = [
        'jobs' => ['Tin đã đăng', 'bg-teal-500'],
        'applications' => ['Nhận hồ sơ', 'bg-slate-400'],
        'interviews' => ['Nhận phỏng vấn', 'bg-amber-400'],
        'passed' => ['Đã tuyển', 'bg-emerald-500'],
        'rejected' => ['Bị loại', 'bg-red-400'],
    ];

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(['week', 'month'])],
            'week' => ['nullable', 'regex:/^\d{4}-W\d{2}$/'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $period = $filters['period'] ?? 'week';
        if ($period === 'month') {
            $from = isset($filters['month']) ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth() : now()->startOfMonth();
            $to = $from->copy()->endOfMonth();
        } else {
            $from = isset($filters['week'])
                ? Carbon::now()->setISODate((int) substr($filters['week'], 0, 4), (int) substr($filters['week'], 6, 2))->startOfWeek()
                : now()->startOfWeek();
            $to = $from->copy()->endOfWeek();
        }

        // The team is currently the employer account itself; the table is built per member to allow more later.
        $members = collect([$request->user()]);

        $rows = $members->map(fn (User $member) => [
            'member' => $member,
            'totals' => $this->metrics($member, $from, $to),
            'breakdown' => $this->buckets($period, $from, $to)->map(fn (array $bucket) => [
                'label' => $bucket['label'],
                'values' => $this->metrics($member, $bucket['from'], $bucket['to']),
            ]),
        ]);

        $team = collect(array_keys(self::METRICS))->mapWithKeys(fn (string $key) => [$key => $rows->sum(fn ($row) => $row['totals'][$key])]);

        return view('employer.kpi.index', [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'team' => $team,
            'metrics' => self::METRICS,
            'prev' => $this->periodQuery($period, $period === 'month' ? $from->copy()->subMonth() : $from->copy()->subWeek()),
            'next' => $this->periodQuery($period, $period === 'month' ? $from->copy()->addMonth() : $from->copy()->addWeek()),
            'isCurrent' => now()->between($from, $to),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function metrics(User $member, Carbon $from, Carbon $to): array
    {
        $applications = Application::query()->whereRelation('jobPost', 'user_id', $member->id);
        $statusChanged = fn (ApplicationStatus $status) => (clone $applications)
            ->where('status', $status)->whereBetween('status_changed_at', [$from, $to])->count();

        return [
            'jobs' => $member->jobPosts()->whereBetween('created_at', [$from, $to])->count(),
            'applications' => (clone $applications)->whereBetween('created_at', [$from, $to])->count(),
            'interviews' => Interview::query()->ownedBy($member)->where('status', '!=', InterviewStatus::Cancelled)
                ->whereBetween('scheduled_at', [$from, $to])->count(),
            'passed' => $statusChanged(ApplicationStatus::Passed),
            'rejected' => $statusChanged(ApplicationStatus::Rejected),
        ];
    }

    /**
     * Days of the week, or weeks of the month, for the expandable breakdown.
     *
     * @return Collection<int, array{label: string, from: Carbon, to: Carbon}>
     */
    private function buckets(string $period, Carbon $from, Carbon $to)
    {
        $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $buckets = collect();

        for ($start = $from->copy(); $start->lte($to);) {
            $end = $period === 'week' ? $start->copy()->endOfDay() : $start->copy()->endOfWeek()->min($to);
            $buckets->push([
                'label' => $period === 'week'
                    ? $days[$start->dayOfWeekIso - 1].' '.$start->format('d/m')
                    : $start->format('d/m').' – '.$end->format('d/m'),
                'from' => $start->copy(),
                'to' => $end->copy(),
            ]);
            $start = $end->copy()->addSecond()->startOfDay();
        }

        return $buckets;
    }

    /**
     * @return array<string, string>
     */
    private function periodQuery(string $period, Carbon $date): array
    {
        return $period === 'month'
            ? ['period' => 'month', 'month' => $date->format('Y-m')]
            : ['period' => 'week', 'week' => $date->format('o-\WW')];
    }
}
