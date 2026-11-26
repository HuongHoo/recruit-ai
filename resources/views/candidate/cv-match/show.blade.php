@extends('layouts.public')
@section('title', 'Độ phù hợp giữa CV và JD')

@section('content')
    <div class="bg-white">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Độ phù hợp giữa CV và JD</h1>
            <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                <span>{{ $job->title }}</span>
                <span aria-hidden="true">·</span>
                <span>{{ $cv->title }}</span>
                <a href="{{ route('jobs.show', $job) }}" class="font-medium text-blue-600 hover:text-blue-700">Xem JD gốc ↗</a>
            </p>

            {{-- CV switcher + (re)analyse --}}
            <div class="mt-6 flex flex-col gap-3 rounded-2xl bg-slate-100 p-4 sm:flex-row sm:items-center">
                <form method="GET" action="{{ route('candidate.cv-match.show', $job) }}" class="flex flex-1 items-center gap-2">
                    <label for="cv_id" class="shrink-0 text-sm font-medium text-slate-700">CV:</label>
                    <select id="cv_id" name="cv_id" class="form-input" onchange="this.form.submit()">
                        @foreach ($cvs as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $cv->id)>{{ $option->title }}{{ $option->is_default ? ' (mặc định)' : '' }}</option>
                        @endforeach
                    </select>
                </form>
                @if ($isSupported)
                    <form method="POST" action="{{ route('candidate.cv-match.store', [$job, 'cv_id' => $cv->id]) }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Đang phân tích... (10-30 giây)'">
                        @csrf
                        @if ($analysis) <input type="hidden" name="refresh" value="1"> @endif
                        <button type="submit" class="btn-primary w-full sm:w-auto">{{ $analysis ? '↻ Phân tích lại' : '✨ Phân tích bằng AI' }}</button>
                    </form>
                @endif
            </div>

            @error('analysis')
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            @unless ($isSupported)
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    CV này là file Word nên AI chưa đọc được. Hãy chọn CV trực tuyến hoặc <a href="{{ route('candidate.cvs.index') }}" class="font-semibold underline">tải lên bản PDF</a>.
                </div>
            @endunless

            @if ($isOutdated)
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    CV hoặc tin tuyển dụng đã thay đổi sau lần phân tích này. Bấm <strong>"Phân tích lại"</strong> để cập nhật kết quả.
                </div>
            @endif

            @if (! $analysis)
                <div class="mt-8 flex flex-col items-center rounded-2xl border border-slate-200 px-6 py-16 text-center">
                    <span class="grid size-16 place-items-center rounded-full bg-blue-50 text-3xl">✨</span>
                    <p class="mt-4 font-semibold text-slate-800">Chưa có phân tích cho CV này</p>
                    <p class="mt-1 max-w-md text-sm text-slate-500">AI sẽ đối chiếu từng yêu cầu trong JD với nội dung CV, chấm điểm mức độ phù hợp và gợi ý cách cải thiện CV trước khi bạn nộp.</p>
                </div>
            @else
                @php
                    $verdict = $analysis->verdict();
                    $counts = $analysis->statusCounts();
                    $rows = $analysis->requirementRows();
                    $circumference = 2 * M_PI * 52;

                    /** Radar geometry: 5 axes starting at the top, values 0-5. */
                    $cx = 150; $cy = 125; $radius = 85;
                    $axes = count($analysis->criteria);
                    $point = function (int $index, float $level) use ($cx, $cy, $radius, $axes): string {
                        $angle = -M_PI / 2 + 2 * M_PI * $index / $axes;
                        return round($cx + cos($angle) * $radius * $level / 5, 1).','.round($cy + sin($angle) * $radius * $level / 5, 1);
                    };
                    $polygon = fn (string $key): string => collect($analysis->criteria)->map(fn ($c, $i) => $point($i, $c[$key]))->implode(' ');
                @endphp

                <div class="mt-8 grid gap-5 lg:grid-cols-[1fr_1.4fr_1.2fr]">
                    {{-- Score ring --}}
                    <section class="flex flex-col items-center rounded-2xl border border-slate-200 p-6 text-center">
                        <h2 class="font-semibold text-slate-900">Mức độ phù hợp</h2>
                        <div class="relative mt-6 size-36">
                            <svg viewBox="0 0 120 120" class="size-full -rotate-90">
                                <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                                <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $verdict['ring'] }}" stroke-width="10" stroke-linecap="round"
                                        stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $circumference * (1 - $analysis->score / 100) }}"/>
                            </svg>
                            <span class="absolute inset-0 grid place-items-center text-4xl font-bold text-slate-900">{{ $analysis->score }}</span>
                        </div>
                        <p class="{{ $verdict['classes'] }} mt-5 font-semibold">● {{ $verdict['label'] }}</p>
                        <p class="mt-3 text-xs text-slate-500">{{ $counts['met'] }} / {{ $rows->count() }} yêu cầu nổi bật đáp ứng</p>
                    </section>

                    {{-- Conclusion --}}
                    <section class="flex flex-col py-2">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $analysis->headline }}</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $analysis->summary }}</p>
                        <p class="mt-auto border-t border-slate-200 pt-4 text-xs text-slate-500">
                            Đánh giá dựa trên nội dung CV và JD, không phải xác suất được tuyển · Phân tích {{ $analysis->updated_at->diffForHumans() }}
                        </p>
                    </section>

                    {{-- Radar chart --}}
                    <section class="rounded-2xl border border-slate-200 p-5">
                        <h2 class="text-sm font-semibold text-slate-900">Tiêu chí chung</h2>
                        <svg viewBox="0 0 300 250" class="mt-2 w-full" role="img" aria-label="Biểu đồ so sánh CV và JD theo tiêu chí">
                            @foreach ([1, 2, 3, 4, 5] as $level)
                                <polygon points="{{ collect($analysis->criteria)->keys()->map(fn ($i) => $point($i, $level))->implode(' ') }}" fill="none" stroke="#e2e8f0"/>
                            @endforeach
                            @foreach ($analysis->criteria as $i => $criterion)
                                <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ explode(',', $point($i, 5))[0] }}" y2="{{ explode(',', $point($i, 5))[1] }}" stroke="#e2e8f0"/>
                                @php [$lx, $ly] = explode(',', $point($i, 6.4)); @endphp
                                <text x="{{ $lx }}" y="{{ $ly }}" text-anchor="middle" dominant-baseline="middle" font-size="9" fill="#475569">{{ $criterion['name'] }}</text>
                            @endforeach
                            <polygon points="{{ $polygon('jd') }}" fill="#2563eb" fill-opacity="0.08" stroke="#2563eb" stroke-width="1.5"/>
                            <polygon points="{{ $polygon('cv') }}" fill="#10b981" fill-opacity="0.25" stroke="#10b981" stroke-width="1.5"/>
                        </svg>
                        <p class="flex justify-center gap-4 text-xs text-slate-500">
                            <span><span class="text-emerald-500">●</span> CV</span>
                            <span><span class="text-blue-600">●</span> JD</span>
                        </p>
                    </section>
                </div>

                {{-- Requirement comparison --}}
                <section class="mt-10">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <h2 class="text-xl font-semibold text-slate-900">Đối chiếu yêu cầu công việc</h2>
                        <div class="flex flex-wrap gap-2 text-xs font-medium">
                            @foreach ($statuses as $status)
                                <span class="{{ $status->badgeClasses() }} rounded-full border px-3 py-1">{{ $status->icon() }} {{ $status->label() }} ({{ $counts[$status->value] }})</span>
                            @endforeach
                        </div>
                    </div>

                    <ul class="mt-4 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                        @foreach ($rows as $row)
                            <li class="grid gap-3 p-5 md:grid-cols-[1.3fr_auto_2fr] md:items-start">
                                <p class="border-l-4 border-slate-200 pl-3 font-medium text-slate-800">{{ $row['requirement'] }}</p>
                                <span class="{{ $row['status']->badgeClasses() }} justify-self-start rounded-full border px-3 py-1 text-xs font-semibold whitespace-nowrap md:w-32 md:text-center">{{ $row['status']->icon() }} {{ $row['status']->label() }}</span>
                                <p class="text-sm text-slate-600">{{ $row['evidence'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <div class="mt-10 grid gap-5 md:grid-cols-2">
                    @if (filled($analysis->strengths))
                        <section class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6">
                            <h2 class="font-semibold text-emerald-900">Điểm mạnh</h2>
                            <ul class="mt-3 space-y-2 text-sm text-emerald-900">
                                @foreach ($analysis->strengths as $strength)
                                    <li class="flex gap-2"><span>✓</span> {{ $strength }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                    @if (filled($analysis->suggestions))
                        <section class="rounded-2xl border border-blue-200 bg-blue-50/50 p-6">
                            <h2 class="font-semibold text-blue-900">Gợi ý cải thiện CV</h2>
                            <ul class="mt-3 space-y-2 text-sm text-blue-900">
                                @foreach ($analysis->suggestions as $suggestion)
                                    <li class="flex gap-2"><span>→</span> {{ $suggestion }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ route('candidate.profile.edit') }}" class="mt-4 inline-block text-sm font-semibold text-blue-700 hover:text-blue-800">Cập nhật hồ sơ &rarr;</a>
                        </section>
                    @endif
                </div>

                <div class="mt-10 flex justify-center">
                    <a href="{{ route('jobs.show', $job) }}#ung-tuyen" class="btn-primary px-6 py-3">Ứng tuyển bằng CV này</a>
                </div>
            @endif
        </div>
    </div>
@endsection
