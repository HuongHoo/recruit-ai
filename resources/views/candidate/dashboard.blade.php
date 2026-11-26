@extends('layouts.app')
@section('title', 'Bảng điều khiển')

@section('content')
    <div class="rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white sm:p-8">
        <h2 class="text-2xl font-bold">Xin chào, {{ auth()->user()->name }}!</h2>
        <p class="mt-2 max-w-xl text-blue-100">Hoàn thiện hồ sơ để nhà tuyển dụng dễ dàng tìm thấy bạn và tăng cơ hội được mời phỏng vấn.</p>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="{{ route('candidate.profile.edit') }}" class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Cập nhật hồ sơ</a>
            <a href="{{ route('jobs.index') }}" class="inline-flex items-center rounded-lg bg-white/15 px-4 py-2 text-sm font-semibold hover:bg-white/25">Tìm việc làm</a>
            <a href="{{ route('candidate.cvs.create') }}" class="inline-flex items-center rounded-lg bg-white/15 px-4 py-2 text-sm font-semibold hover:bg-white/25">Tạo CV trực tuyến</a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['CV của tôi', $cvCount, 'bg-blue-100 text-blue-600'],
            ['Việc đã ứng tuyển', $applicationCount, 'bg-amber-100 text-amber-600'],
            ['Mời phỏng vấn', $interviewCount, 'bg-emerald-100 text-emerald-600'],
            ['Việc đã lưu', $savedCount, 'bg-rose-100 text-rose-600'],
        ] as [$label, $value, $color])
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-2 flex items-center justify-between text-3xl font-bold text-slate-900">
                    {{ $value }}
                    <span class="{{ $color }} size-3 rounded-full"></span>
                </p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
            <div class="flex items-baseline justify-between">
                <h3 class="font-semibold text-slate-900">Ứng tuyển gần đây</h3>
                @if ($recentApplications->isNotEmpty())
                    <a href="{{ route('candidate.applications.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Xem tất cả &rarr;</a>
                @endif
            </div>
            @if ($recentApplications->isNotEmpty())
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($recentApplications as $application)
                        <li class="flex items-center gap-3 py-3">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('jobs.show', $application->jobPost) }}" class="block truncate font-medium text-slate-900 hover:text-blue-700">{{ $application->jobPost->title }}</a>
                                <p class="truncate text-xs text-slate-500">{{ $application->jobPost->company_name }} · {{ $application->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="{{ $application->status->badgeClasses() }} shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $application->status->label() }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
            <div class="mt-6 flex flex-col items-center py-8 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3 21l18-9L3 3l3 9Zm0 0h7"/></svg>
                </span>
                <p class="mt-4 font-medium text-slate-700">Bạn chưa ứng tuyển việc làm nào</p>
                <p class="mt-1 text-sm text-slate-500">Khám phá các cơ hội phù hợp với kỹ năng của bạn.</p>
                <a href="{{ route('jobs.index') }}" class="btn-primary mt-4">Tìm việc làm</a>
            </div>
            @endif
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex items-baseline justify-between">
                <h3 class="font-semibold text-slate-900">Mức độ hoàn thiện hồ sơ</h3>
                <span class="text-lg font-bold text-blue-600">{{ $completion }}%</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-blue-600 transition-all" style="width: {{ $completion }}%"></div>
            </div>
            <ul class="mt-5 space-y-2.5 text-sm">
                @foreach ($checklist as $item)
                    <li>
                        @if ($item['done'])
                            <span class="flex items-center gap-2 text-emerald-700">
                                <span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-xs">✓</span> {{ $item['label'] }}
                            </span>
                        @else
                            <a href="{{ $item['url'] }}" class="group flex items-center gap-2 text-slate-600 hover:text-blue-700">
                                <span class="size-5 rounded-full border-2 border-slate-300 group-hover:border-blue-500"></span>
                                {{ $item['label'] }}
                                <span class="ml-auto text-xs text-blue-600 opacity-0 group-hover:opacity-100">Bổ sung &rarr;</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
@endsection
