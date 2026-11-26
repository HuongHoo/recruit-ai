@extends('layouts.app')
@section('title', 'Quản lý CV')

@section('content')
    <div class="space-y-6">
        <p class="text-sm text-slate-600">
            Chưa biết chọn mẫu nào? <a href="{{ route('cv-templates.index') }}" class="font-semibold text-blue-600 hover:text-blue-700">Xem kho mẫu CV với bản xem trước &rarr;</a>
        </p>

        {{-- Create options --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <a href="{{ route('candidate.cvs.create') }}" class="group flex items-center gap-5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white shadow-lg shadow-blue-600/20 transition hover:shadow-xl">
                <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-white/15">
                    <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/></svg>
                </span>
                <div class="flex-1">
                    <h2 class="text-lg font-bold">Tạo CV trực tuyến</h2>
                    <p class="mt-0.5 text-sm text-blue-100">Chọn mẫu đẹp, thông tin tự lấy từ hồ sơ. Xuất PDF chỉ một nhấp.</p>                </div>
                <span class="text-2xl transition group-hover:translate-x-1">&rarr;</span>
            </a>

            <form id="tai-cv" method="POST" action="{{ route('candidate.cvs.upload') }}" enctype="multipart/form-data" class="scroll-mt-24 rounded-2xl target:border-blue-500 target:bg-blue-50/40 border-2 border-dashed border-slate-300 bg-white p-5 transition hover:border-blue-400">
                @csrf
                <label for="cv-file" class="flex cursor-pointer items-center gap-4">
                    <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3M12 4v12m0-12L7 9m5-5 5 5"/></svg>
                    </span>
                    <span class="flex-1">
                        <span class="block font-semibold text-slate-900">Tải CV có sẵn lên</span>
                        <span id="cv-file-name" class="block text-sm text-slate-500">PDF, DOC, DOCX · tối đa 5 MB</span>
                    </span>
                </label>
                <input id="cv-file" type="file" name="file" accept=".pdf,.doc,.docx" required class="sr-only"
                       onchange="document.getElementById('cv-file-name').textContent = this.files[0]?.name ?? ''; document.getElementById('upload-extra').classList.remove('hidden')">
                <div id="upload-extra" @class(['mt-4 flex gap-2', 'hidden' => ! $errors->has('file') && ! $errors->has('title')])>
                    <input name="title" value="{{ old('title') }}" placeholder="Tên CV (không bắt buộc)" class="form-input">
                    <button type="submit" class="btn-primary shrink-0">Tải lên</button>
                </div>
                @error('file') <p class="form-error">{{ $message }}</p> @enderror
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </form>
        </div>

        {{-- CV list --}}
        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">CV của tôi</h2>
                <span class="text-sm text-slate-500">{{ $cvs->count() }} / {{ $maxCvs }} CV</span>
            </div>

            @if ($cvs->isEmpty())
                <div class="flex flex-col items-center rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
                    <span class="grid size-16 place-items-center rounded-full bg-blue-50 text-blue-500">
                        <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/></svg>
                    </span>
                    <p class="mt-4 font-semibold text-slate-800">Bạn chưa có CV nào</p>
                    <p class="mt-1 max-w-sm text-sm text-slate-500">Tạo CV trực tuyến từ mẫu có sẵn hoặc tải lên CV bạn đã có để bắt đầu ứng tuyển.</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($cvs as $cv)
                        <article @class([
                            'flex flex-col rounded-2xl border bg-white p-5 transition hover:shadow-lg',
                            'border-blue-500 ring-2 ring-blue-500/20' => $cv->is_default,
                            'border-slate-200' => ! $cv->is_default,
                        ])>
                            <div class="flex items-start gap-3">
                                <span @class([
                                    'grid size-11 shrink-0 place-items-center rounded-xl text-xs font-bold',
                                    'bg-blue-50 text-blue-600' => $cv->isOnline(),
                                    'bg-red-50 text-red-600' => ! $cv->isOnline() && $cv->isPdf(),
                                    'bg-sky-50 text-sky-700' => ! $cv->isOnline() && ! $cv->isPdf(),
                                ])>
                                    {{ $cv->isOnline() ? 'CV' : ($cv->isPdf() ? 'PDF' : 'DOC') }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate font-semibold text-slate-900" title="{{ $cv->title }}">{{ $cv->title }}</h3>
                                    <p class="text-xs text-slate-500">
                                        {{ $cv->type->label() }}
                                        · {{ $cv->isOnline() ? 'Mẫu '.$cv->templateName() : $cv->fileSizeLabel() }}
                                    </p>
                                </div>
                                @if ($cv->is_default)
                                    <span class="shrink-0 rounded-full bg-blue-600 px-2.5 py-0.5 text-xs font-semibold text-white">Mặc định</span>
                                @endif
                            </div>

                            <p class="mt-3 text-xs text-slate-400">Cập nhật {{ $cv->updated_at->diffForHumans() }}</p>

                            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 text-sm">
                                <a href="{{ route('candidate.cvs.show', $cv) }}" target="{{ $cv->isOnline() ? '_self' : '_blank' }}" class="rounded-lg bg-slate-100 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-200">Xem</a>
                                <a href="{{ route('candidate.cvs.download', $cv) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-200">{{ $cv->isOnline() ? 'Xuất PDF' : 'Tải về' }}</a>
                                <a href="{{ route('candidate.cvs.edit', $cv) }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-100">Sửa</a>

                                <div class="ml-auto flex items-center gap-1">
                                    @unless ($cv->is_default)
                                        <form method="POST" action="{{ route('candidate.cvs.default', $cv) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-amber-50 hover:text-amber-500" title="Đặt làm mặc định" aria-label="Đặt làm mặc định">
                                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linejoin="round" d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9L12 3Z"/></svg>
                                            </button>
                                        </form>
                                    @endunless
                                    <form method="POST" action="{{ route('candidate.cvs.destroy', $cv) }}" onsubmit="return confirm(@js('Xóa CV "'.$cv->title.'"?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Xóa" aria-label="Xóa">
                                            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-slate-500">★ CV mặc định sẽ được chọn sẵn khi bạn ứng tuyển.</p>
            @endif
        </section>
    </div>
@endsection
