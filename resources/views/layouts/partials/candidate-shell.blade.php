{{-- Candidate pages use the public site chrome (no sidebar); account pages live in the avatar menu. --}}
<body class="flex min-h-full flex-col bg-slate-50 font-sans text-slate-800 antialiased">
    @include('partials.site-header')

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
        <h1 class="mb-6 text-2xl font-bold text-slate-900">@yield('title', 'Tổng quan')</h1>

        @if (session('status'))
            <div class="mb-6 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <p class="mx-auto max-w-6xl px-4 py-6 text-xs text-slate-500 sm:px-6">&copy; {{ date('Y') }} {{ config('app.name') }}. Đồ án tốt nghiệp — Hệ thống tuyển dụng trực tuyến.</p>
    </footer>
    @include('partials.chat-assistant')
</body>
