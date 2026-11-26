@extends('layouts.app')
@section('title', 'Bảng điều khiển')
@section('subtitle', 'Tổng quan hiệu quả tuyển dụng và hồ sơ ứng viên của công ty.')
@section('icon', 'grid')

@php
    $doneSteps = collect($checklist)->where(1, true)->count();
    $maxTrend = max(1, $trend->max('total'));
    $pipelineColors = ['submitted' => 'bg-slate-400', 'viewed' => 'bg-sky-500', 'interview' => 'bg-amber-500', 'passed' => 'bg-teal-500', 'rejected' => 'bg-red-400'];
    $hour = now()->hour;
    $greeting = $hour < 11 ? 'Chào buổi sáng' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối');
@endphp

@section('content')
    {{-- Welcome --}}
    <section class="relative overflow-hidden rounded-2xl bg-[#0b1724] px-6 py-6 text-white sm:px-8">
        <div class="pointer-events-none absolute -top-16 -right-10 size-56 rounded-full bg-teal-500/20 blur-2xl"></div>
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-teal-300">{{ $greeting }}, {{ auth()->user()->name }} 👋</p>
                <h2 class="mt-1 text-2xl font-bold">{{ $company->name ?? 'Chào mừng bạn đến khu vực tuyển dụng' }}</h2>
                <p class="mt-1 text-sm text-slate-300">
                    Bạn có <span class="font-semibold text-white">{{ $unreadCount }}</span> hồ sơ chưa xem và
                    <span class="font-semibold text-white">{{ $newThisWeek }}</span> hồ sơ mới trong 7 ngày qua.
                </p>
            </div>
            <div class="flex shrink-0 gap-2">
                <a href="{{ route('employer.applications.index') }}" class="rounded-lg border border-white/20 px-4 py-2.5 text-sm font-semibold hover:bg-white/10">Xem ứng viên</a>
                <a href="{{ route('employer.jobs.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-400">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                    Tạo tin tuyển dụng
                </a>
            </div>
        </div>
    </section>

    {{-- KPI cards --}}
    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Tin đang hiển thị', $visibleJobCount, 'trên tổng '.$jobCount.' tin', 'M20 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2', 'bg-teal-50 text-teal-600'],
            ['Tổng hồ sơ nhận được', $applicationCount, '+'.$newThisWeek.' trong 7 ngày', 'M9 12h6m-6 4h6M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z', 'bg-blue-50 text-blue-600'],
            ['Hồ sơ chưa xem', $unreadCount, 'cần sàng lọc', 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z', 'bg-amber-50 text-amber-600'],
            ['Mời phỏng vấn', $interviewCount, $passedCount.' ứng viên đạt', 'M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z', 'bg-violet-50 text-violet-600'],
        ] as [$label, $value, $hint, $icon, $iconClass])
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                    <span class="{{ $iconClass }} grid size-9 place-items-center rounded-xl">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($value) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        {{-- Trend chart --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900">Hồ sơ ứng tuyển theo ngày</h3>
                    <p class="text-xs text-slate-500">14 ngày gần nhất · {{ $trend->sum('total') }} hồ sơ</p>
                </div>
            </div>
            <div class="mt-5 flex h-44 items-end gap-1.5 border-b border-slate-200">
                @foreach ($trend as $day)
                    <div class="group relative flex h-full flex-1 flex-col justify-end">
                        <span class="pointer-events-none absolute -top-1 left-1/2 hidden -translate-x-1/2 -translate-y-full rounded bg-slate-900 px-1.5 py-0.5 text-[10px] whitespace-nowrap text-white group-hover:block">{{ $day['label'] }}: {{ $day['total'] }}</span>
                        <div class="rounded-t-md {{ $day['total'] ? 'bg-teal-500 group-hover:bg-teal-600' : 'bg-slate-100' }}" style="height: {{ $day['total'] ? max(6, round($day['total'] / $maxTrend * 100)) : 3 }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-1.5 flex gap-1.5 text-[10px] text-slate-400">
                @foreach ($trend as $i => $day)
                    <span class="flex-1 text-center">{{ $i % 2 === 0 ? $day['label'] : '' }}</span>
                @endforeach
            </div>
        </section>

        {{-- Pipeline --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-semibold text-slate-900">Phễu tuyển dụng</h3>
            <p class="text-xs text-slate-500">Phân bổ hồ sơ theo trạng thái</p>
            <div class="mt-4 flex h-2.5 overflow-hidden rounded-full bg-slate-100">
                @foreach ($pipeline as $row)
                    @if ($row['total'])
                        <div class="{{ $pipelineColors[$row['status']->value] }}" style="width: {{ $row['total'] / max(1, $applicationCount) * 100 }}%"></div>
                    @endif
                @endforeach
            </div>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($pipeline as $row)
                    <li class="flex items-center gap-2.5">
                        <span class="{{ $pipelineColors[$row['status']->value] }} size-2.5 rounded-full"></span>
                        <span class="flex-1 text-slate-600">{{ $row['status']->label() }}</span>
                        <span class="font-semibold text-slate-900">{{ $row['total'] }}</span>
                        <span class="w-10 text-right text-xs text-slate-400">{{ $applicationCount ? round($row['total'] / $applicationCount * 100) : 0 }}%</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        {{-- Recent applications --}}
        <section class="rounded-2xl border border-slate-200 bg-white lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="font-semibold text-slate-900">Hồ sơ ứng tuyển mới</h3>
                <a href="{{ route('employer.applications.index') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-800">Xem tất cả &rarr;</a>
            </div>
            @if ($recentApplications->isNotEmpty())
                <ul class="divide-y divide-slate-100">
                    @foreach ($recentApplications as $application)
                        @php $parts = preg_split('/\s+/', trim($application->candidate->name)); @endphp
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-teal-50 text-xs font-bold text-teal-700">{{ mb_strtoupper(mb_substr(end($parts), 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('employer.applications.show', $application) }}" class="font-medium text-slate-900 hover:text-teal-700">{{ $application->candidate->name }}</a>
                                <p class="truncate text-xs text-slate-500">{{ $application->jobPost->title }} · {{ $application->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="{{ $application->status->badgeClasses() }} rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $application->status->label() }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center px-6 py-12 text-center">
                    <span class="grid size-14 place-items-center rounded-full bg-teal-50 text-teal-500">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m11-12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
                    </span>
                    <p class="mt-4 font-medium text-slate-700">Chưa có hồ sơ ứng tuyển</p>
                    <p class="mt-1 text-sm text-slate-500">Đăng tin tuyển dụng để bắt đầu nhận hồ sơ.</p>
                </div>
            @endif
        </section>

        {{-- Onboarding checklist --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Bắt đầu tuyển dụng</h3>
                <span class="text-xs font-semibold text-teal-700">{{ $doneSteps }}/{{ count($checklist) }}</span>
            </div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-teal-500" style="width: {{ $doneSteps / count($checklist) * 100 }}%"></div>
            </div>
            <ol class="mt-4 space-y-1 text-sm">
                @foreach ($checklist as [$label, $done, $url])
                    <li>
                        <a href="{{ $url }}" class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-50">
                            @if ($done)
                                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-teal-500 text-white">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                </span>
                                <span class="text-slate-400 line-through">{{ $label }}</span>
                            @else
                                <span class="size-6 shrink-0 rounded-full border-2 border-slate-300"></span>
                                <span class="flex-1 font-medium text-slate-700">{{ $label }}</span>
                                <span class="text-slate-400">&rsaquo;</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    {{-- Top jobs --}}
    <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="font-semibold text-slate-900">Tin tuyển dụng nổi bật</h3>
            <a href="{{ route('employer.jobs.index') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-800">Quản lý việc làm &rarr;</a>
        </div>
        @if ($topJobs->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Vị trí</th>
                            <th class="px-5 py-3 font-semibold">Trạng thái</th>
                            <th class="px-5 py-3 font-semibold">Hạn nộp</th>
                            <th class="px-5 py-3 text-right font-semibold">Hồ sơ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($topJobs as $job)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('employer.jobs.show', $job) }}" class="font-semibold text-slate-900 hover:text-teal-700">{{ $job->title }}</a>
                                    <p class="text-xs text-slate-500">{{ $job->location }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span @class(['rounded-full px-2.5 py-0.5 text-xs font-semibold', 'bg-teal-100 text-teal-700' => $job->isVisible(), 'bg-slate-100 text-slate-600' => ! $job->isVisible()])>{{ $job->isVisible() ? 'Đang hiển thị' : 'Đã ẩn' }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $job->deadline?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span class="font-semibold text-slate-900">{{ $job->applications_count }}</span>
                                    @if ($job->new_applications_count)
                                        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ $job->new_applications_count }} mới</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="px-5 py-10 text-center text-sm text-slate-500">Chưa có tin tuyển dụng nào. <a href="{{ route('employer.jobs.create') }}" class="font-semibold text-teal-700">Tạo tin ngay</a></p>
        @endif
    </section>
@endsection
