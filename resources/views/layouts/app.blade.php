<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Bảng điều khiển') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
@php
    use App\Enums\UserRole;

    $user = auth()->user();

    /** Full class strings are kept literal so Tailwind can detect them. */
    $theme = match ($user->role) {
        UserRole::Candidate => [
            'sidebar' => 'bg-white border-r border-slate-200',
            'brandIcon' => 'bg-blue-600 text-white',
            'brandText' => 'text-slate-900',
            'item' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
            'active' => 'bg-blue-50 text-blue-700',
            'section' => 'text-slate-400',
            'avatar' => 'bg-blue-100 text-blue-700',
            'badge' => 'bg-blue-100 text-blue-700',
        ],
        UserRole::Employer => [
            'sidebar' => 'bg-white border-r border-slate-200',
            'brandIcon' => 'bg-emerald-600 text-white',
            'brandText' => 'text-slate-900',
            'item' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
            'active' => 'bg-emerald-50 text-emerald-700',
            'section' => 'text-slate-400',
            'avatar' => 'bg-emerald-100 text-emerald-700',
            'badge' => 'bg-emerald-100 text-emerald-700',
        ],
        UserRole::Admin => [
            'sidebar' => 'bg-slate-900',
            'brandIcon' => 'bg-amber-500 text-slate-900',
            'brandText' => 'text-white',
            'item' => 'text-slate-300 hover:bg-white/10 hover:text-white',
            'active' => 'bg-white/10 text-white',
            'section' => 'text-slate-500',
            'avatar' => 'bg-amber-100 text-amber-800',
            'badge' => 'bg-amber-100 text-amber-800',
        ],
    };

    /** Heroicons outline paths. */
    $icons = [
        'home' => 'M3 12 12 3l9 9M5 10v10h5v-6h4v6h5V10',
        'user' => 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7Z',
        'doc' => 'M9 12h6m-6 4h6M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z',
        'search' => 'm21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z',
        'send' => 'M6 12 3 21l18-9L3 3l3 9Zm0 0h7',
        'heart' => 'M12 21s-7-4.4-9.3-9A5.2 5.2 0 0 1 12 6a5.2 5.2 0 0 1 9.3 6c-2.3 4.6-9.3 9-9.3 9Z',
        'briefcase' => 'M20 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2',
        'plus' => 'M12 5v14m-7-7h14',
        'users' => 'M17 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m18 0v-1a4 4 0 0 0-3-3.9M14 4.1a4 4 0 0 1 0 7.8M14 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
        'building' => 'M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1m-6 4h1m4 0h1m-6 4h1m4 0h1',
        'shield' => 'M12 3 4 6v6c0 5 3.4 8.5 8 9 4.6-.5 8-4 8-9V6l-8-3Z',
        'chart' => 'M4 20V10m6 10V4m6 16v-7m4 7H2',
        'bell' => 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9',
        'cog' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14.5 3h-4l-.4 2.6a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2 1.2l.4 2.6h4l.4-2.6a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2Z',
    ];

    /**
     * Sidebar menu per role. Items without a 'route' are features not built yet.
     *
     * @var array<string, array<int, array{label: string, icon: string, route?: string, active?: array<int, string>}>> $menu
     */
    $menu = match ($user->role) {
        UserRole::Candidate => [
            'Tổng quan' => [
                ['label' => 'Bảng điều khiển', 'icon' => 'home', 'route' => 'candidate.dashboard'],
            ],
            'Tìm việc' => [
                ['label' => 'Tìm việc làm', 'icon' => 'search', 'route' => 'jobs.index'],
                ['label' => 'Việc đã ứng tuyển', 'icon' => 'send', 'route' => 'candidate.applications.index'],
                ['label' => 'Việc đã lưu', 'icon' => 'heart', 'route' => 'candidate.saved-jobs.index'],
                ['label' => 'Thông báo việc làm', 'icon' => 'bell', 'route' => 'candidate.job-alerts.index'],
            ],
            'Hồ sơ' => [
                ['label' => 'Hồ sơ cá nhân', 'icon' => 'user', 'route' => 'candidate.profile.edit', 'active' => ['candidate.profile.*', 'candidate.educations.*', 'candidate.experiences.*']],
                ['label' => 'Quản lý CV', 'icon' => 'doc', 'route' => 'candidate.cvs.index', 'active' => ['candidate.cvs.*']],
            ],
        ],
        UserRole::Employer => [
            'Tổng quan' => [
                ['label' => 'Bảng điều khiển', 'icon' => 'home', 'route' => 'employer.dashboard'],
            ],
            'Tuyển dụng' => [
                ['label' => 'Đăng tin mới', 'icon' => 'plus', 'route' => 'employer.jobs.create'],
                ['label' => 'Tin tuyển dụng', 'icon' => 'briefcase', 'route' => 'employer.jobs.index', 'active' => ['employer.jobs.index', 'employer.jobs.show', 'employer.jobs.edit']],
                ['label' => 'Hồ sơ ứng viên', 'icon' => 'users', 'route' => 'employer.applications.index', 'active' => ['employer.applications.*']],
            ],
            'Công ty' => [
                ['label' => 'Hồ sơ công ty', 'icon' => 'building', 'route' => 'employer.company.edit'],
            ],
        ],
        UserRole::Admin => [
            'Tổng quan' => [
                ['label' => 'Bảng điều khiển', 'icon' => 'home', 'route' => 'admin.dashboard'],
                ['label' => 'Thống kê', 'icon' => 'chart'],
            ],
            'Quản lý' => [
                ['label' => 'Người dùng', 'icon' => 'users', 'route' => 'admin.users.index'],
                ['label' => 'Xác thực công ty', 'icon' => 'building', 'route' => 'admin.companies.index', 'active' => ['admin.companies.*'], 'badge' => \App\Models\Company::query()->where('status', \App\Enums\CompanyStatus::Pending)->count()],
                ['label' => 'Duyệt tin tuyển dụng', 'icon' => 'shield'],
                ['label' => 'Bài viết & sự kiện', 'icon' => 'doc', 'route' => 'admin.posts.index', 'active' => ['admin.posts.*']],
                ['label' => 'Cộng đồng', 'icon' => 'users', 'route' => 'admin.community.index', 'active' => ['admin.community.*'], 'badge' => \App\Models\CommunityReport::query()->whereNull('resolved_at')->count()],
                ['label' => 'Đơn mua gói', 'icon' => 'chart', 'route' => 'admin.orders.index', 'active' => ['admin.orders.*'], 'badge' => \App\Models\Order::query()->where('status', 'pending')->count()],
            ],
            'Hệ thống' => [
                ['label' => 'Cài đặt', 'icon' => 'cog'],
            ],
        ],
    };

    $initials = mb_strtoupper(mb_substr($user->name, 0, 1));
@endphp
@if ($user->isCandidate())
    @include('layouts.partials.candidate-shell')
@elseif ($user->isEmployer())
    @include('layouts.partials.employer-shell')
@else
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased">
    {{-- Mobile backdrop --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden" onclick="toggleSidebar(false)"></div>

    {{-- Sidebar --}}
    <aside id="sidebar" class="{{ $theme['sidebar'] }} fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col transition-transform lg:translate-x-0">
        <div class="flex h-16 items-center gap-2 px-5">
            <span class="{{ $theme['brandIcon'] }} grid size-9 place-items-center rounded-lg">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['briefcase'] }}"/></svg>
            </span>
            <div class="leading-tight">
                <p class="{{ $theme['brandText'] }} font-bold">{{ config('app.name') }}</p>
                <p class="{{ $theme['section'] }} text-xs">{{ $user->role->label() }}</p>
            </div>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
            @foreach ($menu as $section => $items)
                <div>
                    <p class="{{ $theme['section'] }} mb-2 px-3 text-xs font-semibold tracking-wider uppercase">{{ $section }}</p>
                    <ul class="space-y-1">
                        @foreach ($items as $item)
                            @php $isActive = isset($item['route']) && request()->routeIs($item['active'] ?? $item['route']); @endphp
                            <li>
                                @isset($item['route'])
                                    <a href="{{ route($item['route']) }}"
                                       class="{{ $isActive ? $theme['active'] : $theme['item'] }} flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium"
                                       @if ($isActive) aria-current="page" @endif>
                                @else
                                    <span class="{{ $theme['item'] }} flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium opacity-60" title="Chức năng sắp ra mắt">
                                @endisset
                                    <svg class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}"/></svg>
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    @if (! empty($item['badge']))
                                        <span class="rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-bold text-slate-900">{{ $item['badge'] }}</span>
                                    @endif
                                    @unless (isset($item['route']))
                                        <span class="rounded bg-slate-500/15 px-1.5 py-0.5 text-[10px] font-semibold">Sắp có</span>
                                    @endunless
                                @isset($item['route'])
                                    </a>
                                @else
                                    </span>
                                @endisset
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="p-3">
            @csrf
            <button type="submit" class="{{ $theme['item'] }} flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17l5-5-5-5m5 5H9m4 9H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h8"/></svg>
                Đăng xuất
            </button>
        </form>
    </aside>

    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button type="button" class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" onclick="toggleSidebar(true)" aria-label="Mở menu">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="flex-1 truncate text-lg font-semibold text-slate-900">@yield('title', 'Bảng điều khiển')</h1>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                </div>
                <span class="{{ $theme['avatar'] }} grid size-10 place-items-center rounded-full font-bold">{{ $initials }}</span>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div class="mb-6 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('company') || $errors->has('user'))
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first('company') ?: $errors->first('user') }}</div>
            @endif

            @if ($user->isEmployer() && ! request()->routeIs('employer.company.*'))
                @include('employer.company._status-banner', ['company' => $user->company])
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        function toggleSidebar(open) {
            document.getElementById('sidebar').classList.toggle('-translate-x-full', !open);
            document.getElementById('sidebar-backdrop').classList.toggle('hidden', !open);
        }
    </script>
</body>
@endif
</html>
