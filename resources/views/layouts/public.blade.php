<!DOCTYPE html>
<html lang="vi" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Việc làm') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-full flex-col bg-white font-sans text-slate-800 antialiased">
    @include('partials.site-header')

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-slate-950 text-slate-400">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-extrabold text-white">
                    <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                    </span>
                    {{ config('app.name') }}
                </a>
                <p class="mt-4 max-w-sm text-sm leading-relaxed">Nền tảng kết nối ứng viên và nhà tuyển dụng: tìm việc nhanh, tuyển đúng người, minh bạch và an toàn.</p>
            </div>
            @foreach ([
                'Dành cho ứng viên' => ['Tìm việc làm', 'Việc làm nổi bật', 'Tạo hồ sơ', 'Cẩm nang nghề nghiệp'],
                'Dành cho nhà tuyển dụng' => ['Đăng tin tuyển dụng', 'Tìm hồ sơ', 'Bảng giá dịch vụ', 'Liên hệ tư vấn'],
                'Hỗ trợ' => ['Câu hỏi thường gặp', 'Điều khoản sử dụng' => route('legal.terms'), 'Chính sách bảo mật' => route('legal.privacy'), 'Liên hệ'],
            ] as $heading => $links)
                <div>
                    <h4 class="text-sm font-semibold text-white">{{ $heading }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($links as $label => $url)
                            @if (is_string($label))
                                <li><a href="{{ $url }}" class="hover:text-white">{{ $label }}</a></li>
                            @else
                                <li><span class="cursor-default hover:text-white">{{ $url }}</span></li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <div class="border-t border-white/10">
            <p class="mx-auto max-w-7xl px-4 py-6 text-xs sm:px-6">&copy; {{ date('Y') }} {{ config('app.name') }}. Đồ án tốt nghiệp — Hệ thống tuyển dụng trực tuyến.</p>
        </div>
    </footer>

    @stack('scripts')
    @unless (View::hasSection('hide-chat-assistant'))
        @include('partials.chat-assistant')
    @endunless
</body>
</html>
