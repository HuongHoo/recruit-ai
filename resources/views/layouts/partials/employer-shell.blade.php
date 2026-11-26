{{-- Employer workspace: dark collapsible sidebar + page header (title, subtitle, bell). --}}
@php
    $company = $user->company;
    $words = preg_split('/\s+/', trim($user->name));
    $userInitials = mb_strtoupper(mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));

    $eIcons = $icons + [
        'grid' => 'M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z',
        'target' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-5a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0-3a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z',
        'calendar' => 'M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z',
        'receipt' => 'M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6m-6 4h6',
        'box' => 'M4 7h16v13H4V7Zm-1-4h18v4H3V3Zm7 8h4',
        'chat' => 'M8 10h.01M12 10h.01M16 10h.01M21 12a9 9 0 0 1-13.5 7.8L3 21l1.2-4.5A9 9 0 1 1 21 12Z',
    ];

    $eMenu = [
        'Tổng quan' => [
            ['label' => 'Bảng điều khiển', 'icon' => 'grid', 'route' => 'employer.dashboard'],
            ['label' => 'Theo dõi tiến độ & KPI', 'icon' => 'target', 'route' => 'employer.kpi'],
        ],
        'Tuyển dụng' => [
            ['label' => 'Kho ứng viên', 'icon' => 'user', 'route' => 'employer.applications.index', 'active' => ['employer.applications.*']],
            ['label' => 'Quản lý việc làm', 'icon' => 'briefcase', 'route' => 'employer.jobs.index', 'active' => ['employer.jobs.*']],
            ['label' => 'Lịch phỏng vấn', 'icon' => 'calendar', 'route' => 'employer.interviews.index'],
        ],
        'Dịch vụ' => [
            ['label' => 'Gói dịch vụ', 'icon' => 'receipt', 'route' => 'employer.packages.index', 'active' => ['employer.packages.*', 'employer.orders.*']],
            ['label' => 'Gói đã mua', 'icon' => 'box', 'route' => 'employer.purchased.index'],
        ],
        'Hệ thống' => [
            ['label' => 'Cài đặt công ty', 'icon' => 'cog', 'route' => 'employer.settings', 'active' => ['employer.settings', 'employer.company.*']],
        ],
    ];
@endphp
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased">
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden" onclick="toggleSidebar(false)"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-[202px] -translate-x-full flex-col bg-[#0b1724] text-slate-300 transition-transform lg:translate-x-0">
        {{-- Company --}}
        <div class="flex items-center gap-2 border-b border-white/10 px-3 py-4">
            @if ($company?->logo_path)
                <img src="{{ $company->logoUrl() }}" alt="" class="size-9 shrink-0 rounded-lg bg-white object-cover">
            @else
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-teal-600 text-sm font-bold text-white">{{ mb_strtoupper(mb_substr($company->name ?? config('app.name'), 0, 1)) }}</span>
            @endif
            <p class="min-w-0 flex-1 truncate text-[13px] font-semibold text-white" title="{{ $company->name ?? 'Chưa có công ty' }}">{{ $company->name ?? 'Chưa có công ty' }}</p>
            <button type="button" class="grid size-8 shrink-0 place-items-center rounded-lg border border-teal-500/60 text-slate-300 hover:bg-white/10 lg:hidden" onclick="toggleSidebar(false)" aria-label="Đóng menu">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16m6-11-3 3 3 3"/></svg>
            </button>
        </div>

        <nav class="flex-1 space-y-4 overflow-y-auto px-2.5 py-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ($eMenu as $section => $items)
                <div>
                    <p class="mb-1.5 px-2 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{{ $section }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($items as $item)
                            @php $isActive = isset($item['route']) && request()->routeIs($item['active'] ?? $item['route']); @endphp
                            <li>
                                @isset($item['route'])
                                    <a href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif
                                       @class(['flex items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-[12.5px] font-medium',
                                           'bg-teal-600 text-white shadow-lg shadow-teal-900/40' => $isActive,
                                           'hover:bg-white/5 hover:text-white' => ! $isActive])>
                                        <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eIcons[$item['icon']] }}"/></svg>
                                        {{ $item['label'] }}
                                    </a>
                                @else
                                    <span class="flex cursor-not-allowed items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-[12.5px] font-medium opacity-50" title="Chức năng sắp ra mắt">
                                        <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eIcons[$item['icon']] }}"/></svg>
                                        <span class="flex-1">{{ $item['label'] }}</span>
                                    </span>
                                @endisset
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <a href="mailto:{{ config('mail.from.address') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-[12.5px] font-medium text-teal-400 hover:bg-white/5">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eIcons['chat'] }}"/></svg>
                Liên hệ hỗ trợ
            </a>
        </nav>

        {{-- User --}}
        <div class="border-t border-white/10 p-3">
            <div class="flex items-center gap-2.5">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-pink-500 text-xs font-bold text-white">{{ $userInitials }}</span>
                <div class="min-w-0">
                    <p class="truncate text-[13px] font-semibold text-white">{{ $user->name }}</p>
                    <p class="truncate text-[11px] text-slate-400">{{ $user->email }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border border-white/15 py-2 text-[12.5px] font-medium hover:bg-white/5 hover:text-white">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17l5-5-5-5m5 5H9m4 9H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h8"/></svg>
                    Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-[202px]">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3.5 sm:px-6">
            <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" onclick="toggleSidebar(true)" aria-label="Mở menu">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="hidden size-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-700 sm:grid">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eIcons[trim($__env->yieldContent('icon', 'briefcase'))] ?? $eIcons['briefcase'] }}"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-bold text-slate-900">@yield('title', 'Bảng điều khiển')</h1>
                @hasSection('subtitle')
                    <p class="truncate text-xs text-slate-500">@yield('subtitle')</p>
                @endif
            </div>
            <div class="rounded-xl border border-slate-200">
                @include('partials.notification-bell')
            </div>
        </header>

        <main class="p-4 sm:p-6">
            @if (session('status'))
                <div class="mb-5 flex items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('company') || $errors->has('user'))
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first('company') ?: $errors->first('user') }}</div>
            @endif

            @unless (request()->routeIs('employer.company.*'))
                @include('employer.company._status-banner', ['company' => $company])
            @endunless

            @yield('content')
        </main>
    </div>

    <script>
        function toggleSidebar(open) {
            document.getElementById('sidebar').classList.toggle('-translate-x-full', !open);
            document.getElementById('sidebar-backdrop').classList.toggle('hidden', !open);
        }
        document.addEventListener('click', (event) => {
            document.querySelectorAll('details[data-dropdown][open]').forEach((menu) => {
                if (!menu.contains(event.target)) menu.removeAttribute('open');
            });
        });
    </script>
</body>
