@extends('layouts.public')
@section('title', 'Mẫu '.$template['name'])

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-slate-800">Trang chủ</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('cv-templates.index') }}" class="hover:text-slate-800">Mẫu CV</a>
            <span class="mx-1.5">/</span>
            <span class="font-medium text-slate-900">{{ $template['name'] }}</span>
        </nav>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @error('template')
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_340px]">
            {{-- Preview --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                <h1 class="text-xl font-bold text-slate-900">Xem trước mẫu</h1>
                <p class="mt-1 text-sm text-slate-500">Bản xem trước dùng dữ liệu mẫu và chính bố cục của file PDF bạn sẽ tải về.</p>
                <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 p-3 sm:p-5">
                    <div class="mx-auto w-[210mm] bg-white shadow-lg max-sm:[zoom:0.42] sm:[zoom:0.7] xl:[zoom:0.85]" style="--accent: {{ $cv->accent_color }}">
                        @include('candidate.cvs.templates.'.$key)
                    </div>
                </div>
            </section>

            {{-- Use this template --}}
            <aside class="space-y-6 lg:sticky lg:top-24">
                <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-6">
                    <h2 class="text-xl font-bold text-slate-900">Dùng mẫu này</h2>
                    <p class="mt-2 text-sm text-slate-600">Tạo CV mới theo mẫu, hoặc chọn CV đã lưu để hệ thống đưa nội dung vào mẫu cho bạn.</p>

                    <ul class="mt-4 space-y-2.5 text-sm text-slate-700">
                        <li class="flex gap-2"><span class="text-blue-600">文A</span> <span><strong>Ngôn ngữ CV:</strong> {{ $template['language'] ?? 'Tiếng Việt' }}</span></li>
                        <li class="flex gap-2"><span class="text-blue-600">◎</span> {{ ($template['photo'] ?? false) ? 'Cần dán ảnh 3x4 khi in' : 'Không cần ảnh' }}</li>
                        <li class="flex gap-2"><span class="text-blue-600">✓</span> <span>{{ $template['suitable'] }}</span></li>
                        @if ($template['ats'])
                            <li class="flex gap-2"><span class="text-blue-600">⚙</span> Phần mềm lọc CV (ATS) đọc được chính xác</li>
                        @endif
                    </ul>

                    @auth
                        @if (auth()->user()->isCandidate())
                            <a href="{{ route('candidate.cvs.create', ['template' => $key]) }}" class="btn-primary mt-6 w-full rounded-full py-3">Tạo CV theo mẫu ↗</a>
                        @else
                            <p class="mt-6 rounded-lg bg-white px-4 py-3 text-sm text-slate-600">Chức năng tạo CV dành cho tài khoản ứng viên.</p>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-primary mt-6 w-full rounded-full py-3">Đăng nhập để tạo CV theo mẫu</a>
                        <p class="mt-2 text-center text-xs text-slate-500">Chưa có tài khoản? <a href="{{ route('register') }}" class="font-semibold text-blue-600">Đăng ký miễn phí</a></p>
                    @endauth

                    @if ($savedCvs->isNotEmpty())
                        <div class="mt-6 border-t border-blue-100 pt-5">
                            <h3 class="text-sm font-semibold text-slate-900">Đưa CV có sẵn vào mẫu</h3>
                            <ul class="mt-3 space-y-2.5">
                                @foreach ($savedCvs as $saved)
                                    <li class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-slate-900" title="{{ $saved->title }}">{{ $saved->title }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $saved->updated_at->format('Y-m-d') }}
                                                · {{ $saved->isOnline() ? 'Mẫu '.$saved->templateName() : 'File tải lên' }}
                                            </p>
                                        </div>
                                        @if (! $saved->isOnline())
                                            <span class="shrink-0 text-xs text-slate-400" title="CV tải lên là file có sẵn nên không đổi mẫu được">Không hỗ trợ</span>
                                        @elseif ($saved->template === $key)
                                            <span class="shrink-0 text-xs font-semibold text-emerald-600">Đang dùng</span>
                                        @else
                                            <form method="POST" action="{{ route('candidate.cvs.template', $saved) }}" class="shrink-0">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="template" value="{{ $key }}">
                                                <button type="submit" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Dùng CV này</button>
                                            </form>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Mẫu khác</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($otherTemplates as $otherKey => $other)
                            <li>
                                <a href="{{ route('cv-templates.show', $otherKey) }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm hover:border-blue-300">
                                    <span class="font-medium text-slate-800">{{ $other['name'] }}</span>
                                    <span class="text-blue-600">&rarr;</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    </div>
@endsection
