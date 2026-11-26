@extends('layouts.public')
@section('title', 'Kết quả phỏng vấn mô phỏng')

@section('content')
    @php
        $feedback = $interview->feedback;
        $verdict = $interview->verdict();
        $circumference = 2 * M_PI * 52;
    @endphp

    <div class="bg-white">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
            <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('candidate.mock-interviews.index') }}" class="hover:text-slate-800">Luyện phỏng vấn</a> <span aria-hidden="true">›</span>
                <span class="font-medium text-slate-800">Kết quả</span>
            </nav>

            <div class="mt-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Kết quả phỏng vấn mô phỏng</h1>
                    <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                        <a href="{{ route('jobs.show', $job) }}" class="font-medium text-blue-600 hover:text-blue-700">{{ $job->title }}</a>
                        <span aria-hidden="true">·</span><span>{{ $interview->cv_title }}</span>
                        <span aria-hidden="true">·</span><span>{{ $interview->languageName() }}</span>
                        <span aria-hidden="true">·</span><span>{{ $interview->completed_at->format('H:i d/m/Y') }}</span>
                    </p>
                </div>
                @if ($canRetry)
                    <form method="POST" action="{{ route('candidate.mock-interviews.store', $job) }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Đang soạn câu hỏi...'">
                        @csrf
                        <input type="hidden" name="cv_id" value="{{ $interview->cv_id }}">
                        <input type="hidden" name="language" value="{{ $interview->language }}">
                        <button type="submit" class="btn-primary rounded-full px-5">↻ Luyện lại với bộ câu hỏi mới</button>
                    </form>
                @endif
            </div>

            <div class="mt-8 grid gap-5 md:grid-cols-[240px_1fr]">
                <section class="flex flex-col items-center rounded-2xl border border-slate-200 p-6 text-center">
                    <h2 class="font-semibold text-slate-900">Điểm phỏng vấn</h2>
                    <div class="relative mt-5 size-36">
                        <svg viewBox="0 0 120 120" class="size-full -rotate-90">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $verdict['ring'] }}" stroke-width="10" stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $circumference * (1 - $interview->score / 100) }}"/>
                        </svg>
                        <span class="absolute inset-0 grid place-items-center text-4xl font-bold text-slate-900">{{ $interview->score }}</span>
                    </div>
                    <p class="{{ $verdict['classes'] }} mt-4 font-semibold">● {{ $verdict['label'] }}</p>
                    <p class="mt-2 text-xs text-slate-500">Đã trả lời {{ $interview->answeredCount() }} / {{ count($interview->questions) }} câu</p>
                </section>

                <section class="flex flex-col rounded-2xl bg-slate-50 p-6">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $feedback['headline'] }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $feedback['summary'] }}</p>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        @if (filled($feedback['strengths']))
                            <div>
                                <h3 class="text-sm font-semibold text-emerald-700">Điểm mạnh</h3>
                                <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                                    @foreach ($feedback['strengths'] as $strength)
                                        <li class="flex gap-2"><span class="text-emerald-500">✓</span>{{ $strength }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if (filled($feedback['improvements']))
                            <div>
                                <h3 class="text-sm font-semibold text-amber-700">Cần cải thiện</h3>
                                <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                                    @foreach ($feedback['improvements'] as $improvement)
                                        <li class="flex gap-2"><span class="text-amber-500">→</span>{{ $improvement }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    <p class="mt-auto border-t border-slate-200 pt-4 text-xs text-slate-500">Đánh giá do AI thực hiện để bạn luyện tập, không phải kết quả tuyển dụng thật.</p>
                </section>
            </div>

            <h2 class="mt-10 text-xl font-semibold text-slate-900">Nhận xét từng câu</h2>
            <div class="mt-4 space-y-4">
                @foreach ($interview->questions as $i => $question)
                    @php
                        $item = $feedback['items'][$i] ?? ['score' => 0, 'comment' => '', 'suggestion' => ''];
                        $answer = $interview->answers[$i] ?? '';
                    @endphp
                    <details class="group rounded-2xl border border-slate-200" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-start gap-4 p-5">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-blue-600">{{ $question['focus'] }}</p>
                                <p class="mt-1 font-medium text-slate-900">{{ $question['question'] }}</p>
                            </div>
                            <span @class([
                                'shrink-0 rounded-full px-3 py-1 text-sm font-bold',
                                'bg-emerald-50 text-emerald-700' => $item['score'] >= 8,
                                'bg-blue-50 text-blue-700' => $item['score'] >= 6 && $item['score'] < 8,
                                'bg-amber-50 text-amber-700' => $item['score'] >= 4 && $item['score'] < 6,
                                'bg-red-50 text-red-600' => $item['score'] < 4,
                            ])>{{ $item['score'] }}/10</span>
                        </summary>
                        <div class="space-y-4 border-t border-slate-100 px-5 py-5 sm:pl-17">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Bạn đã trả lời</h3>
                                <p class="mt-1 text-sm whitespace-pre-line text-slate-700">{{ filled($answer) ? $answer : '— Bỏ qua —' }}</p>
                            </div>
                            @if (filled($item['comment']))
                                <div>
                                    <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Nhận xét</h3>
                                    <p class="mt-1 text-sm text-slate-700">{{ $item['comment'] }}</p>
                                </div>
                            @endif
                            @if (filled($item['suggestion']))
                                <div class="rounded-xl bg-blue-50 p-4">
                                    <h3 class="text-xs font-semibold tracking-wide text-blue-700 uppercase">Gợi ý câu trả lời</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-slate-700" lang="{{ $interview->speechLang() }}">{{ $item['suggestion'] }}</p>
                                </div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('jobs.show', $job) }}" class="btn-secondary rounded-full">← Về tin tuyển dụng</a>
                <a href="{{ route('candidate.mock-interviews.index') }}" class="btn-secondary rounded-full">Lịch sử luyện phỏng vấn</a>
            </div>
        </div>
    </div>
@endsection
