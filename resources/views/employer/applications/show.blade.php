@extends('layouts.app')
@section('title', 'Hồ sơ: '.$candidate->name)
@section('subtitle', 'Xem CV, thông tin liên hệ và cập nhật trạng thái hồ sơ.')
@section('icon', 'user')

@section('content')
    <a href="{{ route('employer.jobs.show', $application->jobPost) }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; {{ $application->jobPost->title }}</a>

    <div class="mt-4 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <div class="flex items-start gap-4">
                    <span class="grid size-14 shrink-0 place-items-center rounded-full bg-teal-100 text-xl font-bold text-teal-700">{{ mb_strtoupper(mb_substr($candidate->name, 0, 1)) }}</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl font-bold text-slate-900">{{ $candidate->name }}</h2>
                        @if ($profile->headline) <p class="text-slate-600">{{ $profile->headline }}</p> @endif
                        <p class="mt-1 text-sm text-slate-500">{{ $candidate->email }} @if ($profile->phone) · {{ $profile->phone }} @endif</p>
                    </div>
                    <span class="{{ $application->status->badgeClasses() }} rounded-full px-3 py-1 text-xs font-semibold">{{ $application->status->label() }}</span>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="font-semibold text-slate-900">Thư giới thiệu</h3>
                <p class="mt-3 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $application->cover_letter ?: 'Ứng viên không gửi thư giới thiệu.' }}</p>
            </section>

            <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-6">
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-slate-900">CV ứng tuyển</h3>
                    <p class="text-sm text-slate-500">{{ $application->cv_title }}</p>
                </div>
                @if ($application->cv)
                    <a href="{{ route('employer.applications.cv', $application) }}" target="_blank" class="btn-primary bg-teal-600 hover:bg-teal-700">Xem CV</a>
                @else
                    <span class="text-sm text-slate-400">Ứng viên đã xóa CV này</span>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                @if ($videoCv?->hasRecordings())
                    <div class="flex items-center gap-4">
                        @if ($videoCv->photo_path)
                            <img src="{{ route('employer.applications.video-photo', $application) }}" alt="" class="size-14 rounded-full object-cover">
                        @else
                            <span class="grid size-14 place-items-center rounded-full bg-teal-100 font-bold text-teal-700">{{ $videoCv->initials() }}</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-slate-900">Video CV</h3>
                            <p class="text-sm text-slate-600">{{ $videoCv->full_name }} · {{ $videoCv->position }}</p>
                            <p class="text-xs text-slate-500">{{ $videoCv->recordedAnswers()->count() }} câu trả lời · tổng {{ $videoCv->totalDurationLabel() }}</p>
                        </div>
                    </div>
                    @if ($videoCv->intro)
                        <p class="mt-4 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $videoCv->intro }}</p>
                    @endif
                    <div class="mt-5 space-y-5">
                        @foreach ($videoCv->recordedAnswers() as $answer)
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Câu {{ $loop->iteration }}. {{ $answer->question }} <span class="font-normal text-slate-400">· {{ $answer->durationLabel() }}</span></p>
                                <video controls preload="metadata" playsinline class="mt-2 aspect-video w-full rounded-xl bg-slate-900" src="{{ route('employer.applications.video', [$application, $answer]) }}"></video>
                            </div>
                        @endforeach
                    </div>
                @else
                    <h3 class="font-semibold text-slate-900">Video CV</h3>
                    <p class="mt-2 text-sm text-slate-500">Ứng viên chưa có Video CV.</p>
                @endif
            </section>
        </div>

        <aside class="self-start rounded-2xl border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-slate-900">Cập nhật trạng thái</h3>
            <p class="mt-1 text-xs text-slate-500">Ứng viên nhận email khi bạn mời phỏng vấn, chọn Đạt hoặc Không đạt.</p>
            <div class="mt-4 space-y-2">
                @foreach ($statuses as $status)
                    <form method="POST" action="{{ route('employer.applications.update', $application) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status->value }}">
                        <button type="submit" @disabled($application->status === $status) @class([
                            'w-full rounded-lg border px-4 py-2.5 text-left text-sm font-medium transition',
                            'border-teal-500 bg-teal-50 text-teal-800' => $application->status === $status,
                            'border-slate-200 text-slate-700 hover:bg-slate-50' => $application->status !== $status,
                        ])>{{ $status->label() }}</button>
                    </form>
                @endforeach
            </div>
            @error('status') <p class="form-error">{{ $message }}</p> @enderror

            <div class="mt-6 border-t border-slate-100 pt-5">
                <h3 class="font-semibold text-slate-900">Lịch phỏng vấn</h3>
                @forelse ($application->interviews as $interview)
                    <a href="{{ route('employer.interviews.index', ['range' => 'custom', 'from' => $interview->scheduled_at->format('Y-m-d'), 'to' => $interview->scheduled_at->format('Y-m-d')]) }}" class="mt-3 block rounded-lg border border-slate-200 px-3 py-2 text-sm hover:border-teal-300">
                        <span class="flex items-center justify-between gap-2">
                            <span class="font-medium text-slate-800">{{ $interview->scheduled_at->format('d/m/Y') }} · {{ $interview->timeRange() }}</span>
                            <span class="{{ $interview->status->badgeClasses() }} rounded-full px-2 py-0.5 text-[11px] font-semibold">{{ $interview->status->label() }}</span>
                        </span>
                        <span class="text-xs text-slate-500">{{ $interview->type->label() }}</span>
                    </a>
                @empty
                    <p class="mt-2 text-xs text-slate-500">Chưa có lịch phỏng vấn cho hồ sơ này.</p>
                @endforelse
                <a href="{{ route('employer.interviews.index', ['application' => $application->id]) }}" class="mt-3 flex items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">+ Lên lịch phỏng vấn</a>
            </div>
            <p class="mt-4 text-xs text-slate-400">Nộp lúc {{ $application->created_at->format('d/m/Y H:i') }}</p>
        </aside>
    </div>
@endsection
