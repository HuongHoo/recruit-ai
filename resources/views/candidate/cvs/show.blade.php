<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- The <title> becomes the default file name when saving as PDF. --}}
    <title>{{ \Illuminate\Support\Str::slug($user->name) }}-{{ \Illuminate\Support\Str::slug($cv->title) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 0; }
        @media print {
            html, body { background: #fff !important; }
            .cv-page { box-shadow: none !important; margin: 0 !important; }
        }
        .cv-page { width: 210mm; min-height: 297mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .cv-section { break-inside: avoid; }
    </style>
</head>
<body class="bg-slate-200 font-sans text-slate-800 antialiased" style="--accent: {{ $cv->accent_color ?? '#2563eb' }}">
    {{-- Toolbar (hidden when printing) --}}
    <div class="sticky top-0 z-10 border-b border-slate-300 bg-white/95 backdrop-blur print:hidden">
        <div class="mx-auto flex max-w-[210mm] flex-wrap items-center gap-3 px-4 py-3">
            @isset($backUrl)
                {{-- Viewed by an employer from an application --}}
                <a href="{{ $backUrl }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Quay lại hồ sơ ứng tuyển</a>
                <span class="flex-1 truncate font-semibold text-slate-900">{{ $cv->title }}</span>
            @else
                <a href="{{ route('candidate.cvs.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Danh sách CV</a>
                <span class="flex-1 truncate font-semibold text-slate-900">{{ $cv->title }}</span>
                <a href="{{ route('candidate.profile.edit') }}" class="btn-secondary py-2">Sửa nội dung</a>
                <a href="{{ route('candidate.cvs.edit', $cv) }}" class="btn-secondary py-2">Đổi mẫu</a>
            @endisset
            <button type="button" onclick="window.print()" class="btn-primary py-2">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0-5-5m5 5 5-5M4 20h16"/></svg>
                Tải PDF
            </button>
        </div>
        <p class="mx-auto max-w-[210mm] px-4 pb-2 text-xs text-slate-500">
            Mẹo: trong hộp thoại in, chọn máy in <strong>"Lưu dưới dạng PDF"</strong>, khổ A4, bật <strong>"Đồ họa nền"</strong> và tắt "Đầu trang/chân trang".
        </p>
    </div>

    @if (session('status'))
        <div class="mx-auto mt-4 max-w-[210mm] rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 print:hidden">{{ session('status') }}</div>
    @endif

    <div class="overflow-x-auto py-8 print:overflow-visible print:p-0">
        <div class="cv-page mx-auto bg-white shadow-2xl">
            @include('candidate.cvs.templates.'.$cv->template)
        </div>
    </div>

    @if (request()->boolean('print'))
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
    @endif
</body>
</html>
