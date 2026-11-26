<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-white font-sans text-slate-800 antialiased">
    <div class="flex min-h-full">
        {{-- Brand panel (hidden on mobile) --}}
        <aside class="relative hidden w-5/12 flex-col justify-between overflow-hidden bg-gradient-to-br from-blue-700 to-indigo-900 p-12 text-white lg:flex">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold">
                <span class="grid size-9 place-items-center rounded-lg bg-white/15">
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                </span>
                {{ config('app.name') }}
            </a>
            <div>
                <h2 class="text-3xl font-bold leading-tight">Cơ hội nghề nghiệp<br>bắt đầu từ đây.</h2>
                <p class="mt-4 max-w-sm text-blue-100">Kết nối hàng nghìn ứng viên tài năng với những nhà tuyển dụng hàng đầu.</p>
            </div>
            <p class="text-sm text-blue-200">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            <div class="pointer-events-none absolute -right-24 -bottom-24 size-96 rounded-full bg-white/5"></div>
        </aside>

        {{-- Form panel --}}
        <main class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:px-16">
            <div class="mx-auto w-full @yield('container_width', 'max-w-md')">
                <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
                    &larr; Về trang chủ
                </a>
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
