{{-- Public site header, shared by guests and signed-in candidates. --}}
@php
    $authUser = auth()->user();
    $isCandidate = $authUser?->isCandidate() ?? false;

    if ($authUser) {
        $words = preg_split('/\s+/', trim($authUser->name));
        $initials = mb_strtoupper(mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
    }

    $candidateLinks = [
        ['Tổng quan', 'candidate.dashboard'],
        ['Hồ sơ cá nhân', 'candidate.profile.edit'],
        ['Quản lý CV', 'candidate.cvs.index'],
        ['Video CV', 'candidate.video-cv.edit'],
        ['Việc làm đã lưu', 'candidate.saved-jobs.index'],
        ['Việc đã ứng tuyển', 'candidate.applications.index'],
        ['Luyện phỏng vấn AI', 'candidate.mock-interviews.index'],
        ['Thông báo việc làm', 'candidate.job-alerts.index'],
    ];
@endphp
<header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 sm:px-6">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 text-lg font-extrabold tracking-tight text-slate-900">
            <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-600/30">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            </span>
            {{ config('app.name') }}
        </a>

        @php
            $mainNav = [
                ['Việc làm', route('jobs.index'), ['jobs.*']],
                ['Luyện phỏng vấn với AI', route('candidate.mock-interviews.index'), ['candidate.mock-interviews.*']],
                ['Tạo CV', route('cv-builder'), ['cv-builder', 'cv-templates.*', 'candidate.cvs.*']],
            ];
            $mainNav[] = ['Video CV', route('video-cv'), ['video-cv', 'candidate.video-cv.*']];
            $mainNav[] = ['Cộng đồng', route('community.index'), ['community.*']];
        @endphp
        <nav class="hidden h-full flex-1 items-center gap-8 pl-4 text-[15px] font-semibold text-slate-600 md:flex">
            @foreach ($mainNav as [$label, $url, $patterns])
                @php $active = request()->routeIs(...$patterns); @endphp
                <a href="{{ $url }}" @class([
                    'relative py-2 whitespace-nowrap transition-colors hover:text-blue-700',
                    'after:absolute after:inset-x-0 after:-bottom-1.5 after:h-0.5 after:rounded-full after:bg-blue-700',
                    'text-blue-700 after:block' => $active,
                    'after:hidden' => ! $active,
                ]) @if ($active) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-3 text-sm md:ml-0">
            @auth
                @include('partials.notification-bell')

                {{-- Avatar menu --}}
                <details data-dropdown class="group relative">
                    <summary class="grid size-10 cursor-pointer list-none place-items-center rounded-full bg-blue-600 text-xs font-bold text-white ring-2 ring-blue-100 hover:bg-blue-700 [&::-webkit-details-marker]:hidden" aria-label="Tài khoản">{{ $initials }}</summary>
                    <div class="absolute right-0 z-40 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-900/10">
                        <div class="border-b border-slate-100 px-3 pt-1 pb-3">
                            <p class="truncate font-semibold text-slate-900">{{ $authUser->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $authUser->email }}</p>
                        </div>
                        <ul class="py-1">
                            @if ($isCandidate)
                                @foreach ($candidateLinks as [$label, $route])
                                    <li><a href="{{ route($route) }}" @class(['block rounded-lg px-3 py-2 hover:bg-slate-50', 'bg-blue-50 font-semibold text-blue-700' => request()->routeIs($route)])>{{ $label }}</a></li>
                                @endforeach
                            @else
                                <li><a href="{{ $authUser->homeUrl() }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Trang quản lý</a></li>
                            @endif
                        </ul>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 pt-1">
                            @csrf
                            <button class="w-full rounded-lg px-3 py-2 text-left text-red-600 hover:bg-red-50">Đăng xuất</button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 font-semibold text-slate-700 hover:bg-slate-100 sm:block">Đăng nhập</a>
                <a href="{{ route('register') }}" class="btn-primary">Đăng ký</a>
            @endauth

            @if (! $authUser || $isCandidate)
                <span class="hidden h-8 w-px bg-slate-200 lg:block"></span>
                <a href="{{ $authUser ? route('home').'#nha-tuyen-dung' : route('register', ['role' => 'employer']) }}" class="hidden whitespace-nowrap text-slate-600 hover:text-blue-700 lg:block">Dành cho nhà tuyển dụng</a>
            @endif
        </div>
    </div>
</header>

<script>
    // Close header dropdowns (bell, avatar) when clicking outside them or pressing Escape.
    document.addEventListener('click', (event) => {
        document.querySelectorAll('details[data-dropdown][open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') document.querySelectorAll('details[data-dropdown][open]').forEach((menu) => menu.removeAttribute('open'));
    });
</script>
