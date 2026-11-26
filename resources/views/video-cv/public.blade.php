@extends('layouts.public')
@section('title', 'Video CV · '.$videoCv->full_name)

{{--
    Public Video CV page (share link) and the candidate's own preview of it.
    Media URLs are passed in so the same view works for /v/{token} and the owner preview.
--}}
@section('content')
    <div class="bg-slate-50">
        @if ($isPreview)
            <div class="border-b border-amber-200 bg-amber-50">
                <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-3 px-4 py-3 text-sm text-amber-900 sm:px-6">
                    <span class="flex-1">Đây là <b>bản xem trước</b>. Nhà tuyển dụng sẽ thấy trang giống hệt như vậy khi mở link chia sẻ.</span>
                    <a href="{{ route('candidate.video-cv.edit', ['step' => 'share']) }}" class="font-semibold text-amber-900 underline">&larr; Quay lại chỉnh sửa</a>
                </div>
            </div>
        @elseif ($isOwner)
            <div class="border-b border-blue-200 bg-blue-50">
                <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-3 px-4 py-3 text-sm text-blue-900 sm:px-6">
                    <span class="flex-1">Bạn đang xem Video CV của chính mình qua link chia sẻ · {{ number_format($videoCv->view_count) }} lượt xem.</span>
                    <a href="{{ route('candidate.video-cv.edit', ['step' => 'share']) }}" class="font-semibold underline">Quản lý chia sẻ</a>
                </div>
            </div>
        @endif

        <div class="mx-auto grid max-w-5xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-3">
            {{-- Profile --}}
            <aside class="space-y-6 self-start lg:sticky lg:top-24">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 text-center">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $videoCv->full_name }}" class="mx-auto size-28 rounded-full object-cover ring-4 ring-blue-50">
                    @else
                        <span class="mx-auto grid size-28 place-items-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-3xl font-bold text-white ring-4 ring-blue-50">{{ $videoCv->initials() }}</span>
                    @endif
                    <h1 class="mt-4 text-xl font-bold text-slate-900">{{ $videoCv->full_name }}</h1>
                    <p class="mt-1 font-medium text-blue-700">{{ $videoCv->position }}</p>
                    @if ($videoCv->public_email)
                        <a href="mailto:{{ $videoCv->public_email }}" class="mt-3 inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-blue-700">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                            {{ $videoCv->public_email }}
                        </a>
                    @endif

                    @if ($cvUrl)
                        <a href="{{ $cvUrl }}" target="_blank" rel="noopener" class="btn-primary mt-5 w-full justify-center rounded-full">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                            Xem CV
                        </a>
                    @endif

                    <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-5 text-left text-sm">
                        <div><dt class="text-xs text-slate-500">Câu trả lời</dt><dd class="font-semibold text-slate-900">{{ $answers->count() }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Tổng thời lượng</dt><dd class="font-semibold text-slate-900">{{ $videoCv->totalDurationLabel() }}</dd></div>
                    </dl>
                </section>

                @if ($videoCv->intro)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6">
                        <h2 class="text-sm font-semibold tracking-wider text-slate-500 uppercase">Giới thiệu</h2>
                        <p class="mt-3 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $videoCv->intro }}</p>
                    </section>
                @endif
            </aside>

            {{-- Answers --}}
            <div class="space-y-6 lg:col-span-2">
                @forelse ($answers as $answer)
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="flex items-start gap-3 p-5">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-blue-50 text-sm font-bold text-blue-700">{{ $loop->iteration }}</span>
                            <h2 class="min-w-0 flex-1 pt-1 font-semibold text-slate-900">{{ $answer->question }}</h2>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $answer->durationLabel() }}</span>
                        </div>
                        <video controls preload="metadata" playsinline class="aspect-video w-full bg-slate-900" src="{{ $answerUrl($answer) }}"></video>
                    </section>
                @empty
                    <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Chưa có câu trả lời nào được quay.</section>
                @endforelse

                @unless ($isOwner)
                    <section class="flex flex-col items-center gap-4 rounded-2xl bg-gradient-to-br from-blue-700 to-indigo-700 p-6 text-white sm:flex-row">
                        <div class="flex-1 text-center sm:text-left">
                            <p class="font-semibold">Video CV được tạo trên {{ config('app.name') }}</p>
                            <p class="text-sm text-blue-100">Miễn phí, quay ngay trên trình duyệt trong khoảng 10 phút.</p>
                        </div>
                        <a href="{{ route('video-cv') }}" class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50">Tạo Video CV của bạn</a>
                    </section>
                @endunless
            </div>
        </div>
    </div>
@endsection
