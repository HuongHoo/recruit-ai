@extends('layouts.public')
@section('title', 'Tìm việc làm, tuyển dụng nhanh')

@section('content')
    {{-- ============ HERO + SEARCH ============ --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-950 via-blue-800 to-blue-600 text-white">
        {{-- Diagonal light streaks --}}
        <div class="pointer-events-none absolute inset-y-0 right-[18%] w-64 -skew-x-[35deg] bg-white/5"></div>
        <div class="pointer-events-none absolute inset-y-0 right-[8%] w-px -skew-x-[35deg] bg-white/15"></div>

        <div class="relative mx-auto max-w-5xl px-4 pt-14 pb-10 sm:px-6">
            <div class="text-center">
                <h1 class="text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl lg:text-5xl">Tìm việc nhanh, ứng tuyển dễ dàng</h1>
                <p class="mt-3 text-sm text-blue-100 sm:text-base">Kết nối với hàng nghìn doanh nghiệp uy tín. Tạo hồ sơ một lần, ứng tuyển mọi nơi.</p>
            </div>

            <form method="GET" action="{{ route('jobs.index') }}" class="mt-8">
                <div class="flex flex-col gap-2 rounded-2xl bg-white p-2 text-slate-800 shadow-2xl shadow-blue-950/40 sm:flex-row sm:items-center sm:rounded-full">
                    <label class="flex flex-1 items-center gap-3 px-4">
                        <svg class="size-5 shrink-0 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
                        <span class="sr-only">Từ khóa</span>
                        <input type="search" name="keyword" placeholder="Tên công việc, từ khóa..." class="w-full border-0 bg-transparent py-3 text-base placeholder:text-slate-400 focus:outline-none">
                    </label>
                    <span class="hidden h-8 w-px bg-slate-200 sm:block"></span>
                    <label class="flex items-center gap-2 px-4 sm:w-64">
                        <svg class="size-5 shrink-0 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11.5a7 7 0 1 1 14 0C19 14.8 12 21 12 21Zm0-9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg>
                        <span class="sr-only">Địa điểm</span>
                        <select name="location" class="w-full cursor-pointer border-0 bg-transparent py-3 text-sm text-slate-600 focus:outline-none">
                            @foreach ($locations as $index => $location)
                                <option value="{{ $index === 0 ? '' : $location }}">{{ $index === 0 ? 'Địa điểm làm việc' : $location }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-7 py-3 font-semibold text-white transition hover:bg-blue-700 sm:rounded-full">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
                        Tìm việc làm
                    </button>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-center gap-2 text-sm">
                    <span class="text-blue-100">Gợi ý:</span>
                    @foreach (array_slice($keywords, 0, 5) as $keyword)
                        <a href="{{ route('jobs.index', ['keyword' => $keyword]) }}" class="rounded-full border border-white/25 bg-white/10 px-4 py-1.5 font-medium text-white transition hover:bg-white/20">{{ $keyword }}</a>
                    @endforeach
                </div>
            </form>

            {{-- Stats --}}
            <dl class="mt-10 grid grid-cols-2 border-t border-white/15 pt-6 sm:max-w-xl">
                @foreach (array_slice($stats, 0, 2) as $i => [$value, $label])
                    <div @class(['flex flex-col-reverse items-center', 'border-l border-white/15' => $i > 0])>
                        <dt class="mt-1 text-xs text-blue-100 sm:text-sm">{{ $label }}</dt>
                        <dd class="text-2xl font-extrabold sm:text-3xl">{{ number_format($value, 0, ',', '.') }}+</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ============ FEATURED JOBS ============ --}}
    <section id="viec-lam" class="mx-auto max-w-7xl scroll-mt-20 px-4 py-20 sm:px-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold tracking-wider text-blue-600 uppercase">Cơ hội mới nhất</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Việc làm nổi bật</h2>
            </div>
            <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Xem tất cả việc làm &rarr;</a>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($featuredJobs as $index => $job)
                @php $isSaved = $savedJobIds !== null && in_array($job->id, $savedJobIds, true); @endphp
                <article class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl hover:shadow-blue-900/5">
                    <div class="flex items-start gap-4">
                        <span class="{{ $jobColors[$index % count($jobColors)] }} grid size-12 shrink-0 place-items-center rounded-xl text-lg font-bold text-white">
                            {{ mb_substr(str_replace(['Công ty CP ', 'Công ty TNHH ', 'Tập đoàn ', 'Ngân hàng TMCP ', 'Hệ thống '], '', $job->company_name), 0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-slate-900 group-hover:text-blue-700">
                                <a href="{{ route('jobs.show', $job) }}" class="after:absolute after:inset-0">{{ $job->title }}</a>
                            </h3>
                            <p class="mt-0.5 flex items-center gap-1 truncate text-sm text-slate-500">
                                {{ $job->company_name }}
                                <svg class="size-4 shrink-0 text-blue-500" fill="currentColor" viewBox="0 0 24 24" aria-label="Đã xác minh"><path d="M12 2 9.5 4.5 6 4l-.5 3.5L2 9l2 3-2 3 3.5 1.5L6 20l3.5-.5L12 22l2.5-2.5L18 20l.5-3.5L22 15l-2-3 2-3-3.5-1.5L18 4l-3.5.5L12 2Zm-1.2 14.2-3.5-3.5 1.4-1.4 2.1 2.1 4.9-4.9 1.4 1.4-6.3 6.3Z"/></svg>
                            </p>
                        </div>
                        @if ($savedJobIds !== null)
                            <form method="POST" action="{{ $isSaved ? route('candidate.saved-jobs.destroy', $job) : route('candidate.saved-jobs.store', $job) }}" class="relative z-10">
                                @csrf
                                @if ($isSaved) @method('DELETE') @endif
                                <button type="submit" @class(['rounded-full p-1.5 hover:bg-rose-50', 'text-rose-500' => $isSaved, 'text-slate-400 hover:text-rose-500' => ! $isSaved]) aria-label="{{ $isSaved ? 'Bỏ lưu' : 'Lưu việc làm' }}">
                                    <svg class="size-5" fill="{{ $isSaved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.4-9.3-9A5.2 5.2 0 0 1 12 6a5.2 5.2 0 0 1 9.3 6c-2.3 4.6-9.3 9-9.3 9Z"/></svg>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="relative z-10 rounded-full p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-500" aria-label="Đăng nhập để lưu việc làm">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.4-9.3-9A5.2 5.2 0 0 1 12 6a5.2 5.2 0 0 1 9.3 6c-2.3 4.6-9.3 9-9.3 9Z"/></svg>
                            </a>
                        @endif
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2 text-xs font-medium">
                        @if ($job->deadline && $job->deadline->lte(today()->addDays(7)))
                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-red-600">🔥 Sắp hết hạn</span>
                        @endif
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">{{ $job->salaryLabel() }}</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ $job->location }}</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ $job->job_type->label() }}</span>
                    </div>
                </article>
            @empty
                <p class="col-span-full rounded-2xl border border-slate-200 px-6 py-12 text-center text-slate-500">Chưa có tin tuyển dụng nào. Hãy quay lại sau nhé!</p>
            @endforelse
        </div>
    </section>

    {{-- ============ CATEGORIES ============ --}}
    <section id="nganh-nghe" class="scroll-mt-20 bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="text-center">
                <p class="text-sm font-semibold tracking-wider text-blue-600 uppercase">Khám phá</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Ngành nghề được quan tâm</h2>
            </div>
            <div class="mt-10 grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <a href="{{ $category['url'] }}" class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-300 hover:shadow-lg">
                        <span class="grid size-11 place-items-center rounded-xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $category['icon'] }}"/></svg>
                        </span>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ $category['name'] }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ number_format($category['jobs'], 0, ',', '.') }} việc làm</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ HOW IT WORKS ============ --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold tracking-wider text-blue-600 uppercase">Đơn giản & nhanh chóng</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Ứng tuyển chỉ với 3 bước</h2>
                <p class="mt-4 text-slate-600">Không cần gửi email rườm rà. Tạo hồ sơ một lần và theo dõi mọi đơn ứng tuyển tại một nơi.</p>

                <ol class="mt-8 space-y-6">
                    @foreach ([
                        ['Tạo tài khoản & hồ sơ', 'Điền thông tin, kinh nghiệm và tải lên CV của bạn.'],
                        ['Tìm việc phù hợp', 'Lọc theo ngành nghề, địa điểm, mức lương mong muốn.'],
                        ['Ứng tuyển & theo dõi', 'Nộp hồ sơ một chạm và nhận thông báo khi được phản hồi.'],
                    ] as $index => [$title, $description])
                        <li class="flex gap-4">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 font-bold text-white shadow-lg shadow-blue-600/30">{{ $index + 1 }}</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <a href="{{ route('register') }}" class="btn-primary mt-8 px-6 py-3">Bắt đầu ngay — miễn phí</a>
            </div>

            {{-- Mock application status card --}}
            <div class="relative">
                <div class="absolute inset-0 -rotate-3 rounded-3xl bg-gradient-to-br from-blue-200 to-indigo-200"></div>
                <div class="relative rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">Hồ sơ đã ứng tuyển</h3>
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">3 đơn</span>
                    </div>
                    <ul class="mt-5 space-y-3">
                        @foreach ([
                            ['Lập trình viên PHP', 'Phần mềm Minh An', 'Mời phỏng vấn', 'bg-emerald-50 text-emerald-700'],
                            ['Chuyên viên Kinh doanh', 'Công nghệ Sao Việt', 'Đã xem hồ sơ', 'bg-blue-50 text-blue-700'],
                            ['Nhân viên Marketing', 'Hòa Phát Xanh', 'Đang chờ', 'bg-amber-50 text-amber-700'],
                        ] as [$title, $company, $status, $statusColor])
                            <li class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 p-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $title }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $company }}</p>
                                </div>
                                <span class="{{ $statusColor }} shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold">{{ $status }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-5 rounded-xl bg-emerald-50 p-4">
                        <p class="text-sm font-semibold text-emerald-800">🎉 Chúc mừng!</p>
                        <p class="mt-1 text-xs text-emerald-700">Phần mềm Minh An mời bạn phỏng vấn vào 9:00 thứ Hai tuần tới.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ TOP COMPANIES ============ --}}
    <section id="cong-ty" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-16 sm:px-6">
        {{-- Banner --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-950 via-blue-900 to-blue-700 px-6 py-8 text-white sm:px-8">
            <div class="pointer-events-none absolute inset-y-0 left-1/2 hidden w-px -skew-x-[20deg] bg-white/40 md:block"></div>
            <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-1/2 md:block" aria-hidden="true">
                @foreach ($companies->take(6) as $i => $company)
                    @php $spots = ['top-6 left-[12%]', '-top-4 left-[34%]', 'top-14 left-[54%]', '-top-6 left-[72%]', 'top-10 right-[4%]', 'top-24 left-[30%]']; @endphp
                    <img src="{{ $company->logoUrl() }}" alt="" class="{{ $spots[$i] }} absolute size-20 rotate-[-8deg] rounded-2xl bg-white/90 object-contain p-2 opacity-25 shadow-lg">
                @endforeach
            </div>
            <div class="relative">
                <h2 class="text-2xl font-bold sm:text-3xl">Doanh nghiệp hàng đầu</h2>
                <p class="mt-2 text-sm text-blue-100">Những nhà tuyển dụng đang tuyển nhiều vị trí hấp dẫn nhất</p>
            </div>
        </div>

        @if ($companies->isEmpty())
            <p class="mt-5 rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center text-slate-500">Chưa có doanh nghiệp nào đăng tin.</p>
        @else
            <div data-company-slider data-per-page="3">
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    @foreach ($companies as $company)
                        <article data-slide class="flex flex-col overflow-hidden rounded-2xl border border-blue-200 bg-white transition hover:shadow-lg hover:shadow-blue-900/10">
                            <div class="flex items-center gap-4 bg-gradient-to-r from-blue-950 to-blue-700 p-5 text-white">
                                <img src="{{ $company->logoUrl() }}" alt="Logo {{ $company->name }}" class="size-16 shrink-0 rounded-lg bg-white object-contain p-1.5">
                                <div class="min-w-0">
                                    <h3 class="line-clamp-2 font-bold leading-snug">{{ $company->name }}</h3>
                                    @if ($company->industry)
                                        <p class="mt-1 truncate text-xs text-blue-100">{{ $company->industry }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col px-5 pb-5">
                                @if ($company->open_jobs_count > 0)
                                    <ul class="mt-3 divide-y divide-slate-200 border-y border-slate-200 text-sm">
                                        @foreach ($company->jobPosts as $job)
                                            <li>
                                                <a href="{{ route('jobs.show', $job) }}" class="flex items-center justify-between gap-3 py-2.5 hover:text-blue-700">
                                                    <span class="truncate">{{ $job->title }}</span>
                                                    <span class="shrink-0 text-slate-600">{{ $job->salaryLabel() }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('jobs.index', ['keyword' => $company->name]) }}" class="mt-auto pt-5 text-sm font-semibold text-blue-700 hover:text-blue-800">{{ $company->open_jobs_count }} việc làm đang tuyển &rarr;</a>
                                @else
                                    <p class="mt-4 line-clamp-2 text-sm text-slate-500">{{ $company->description ?: 'Doanh nghiệp hiện chưa có vị trí đang tuyển.' }}</p>
                                    <a href="{{ $company->website }}" target="_blank" rel="noopener" class="mt-auto pt-5 text-sm font-semibold text-blue-700 hover:text-blue-800">Xem doanh nghiệp &rarr;</a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($companies->count() > 3)
                    <div class="mt-5 flex items-center justify-center gap-4 text-sm">
                        <button type="button" data-prev class="grid size-9 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm hover:text-blue-700 disabled:opacity-40" aria-label="Trang trước">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <span><b data-page class="text-blue-700">1</b> <span class="text-slate-400">/ <span data-total>{{ (int) ceil($companies->count() / 3) }}</span></span></span>
                        <button type="button" data-next class="grid size-9 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm hover:text-blue-700 disabled:opacity-40" aria-label="Trang sau">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                @endif
            </div>

            <script>
                document.querySelectorAll('[data-company-slider]').forEach((slider) => {
                    const slides = [...slider.querySelectorAll('[data-slide]')];
                    const perPage = Number(slider.dataset.perPage);
                    const total = Math.ceil(slides.length / perPage);
                    const prev = slider.querySelector('[data-prev]');
                    const next = slider.querySelector('[data-next]');
                    let page = 0;
                    const render = () => {
                        slides.forEach((slide, i) => slide.classList.toggle('hidden', Math.floor(i / perPage) !== page));
                        if (!prev) return;
                        slider.querySelector('[data-page]').textContent = page + 1;
                        prev.disabled = page === 0;
                        next.disabled = page === total - 1;
                    };
                    prev?.addEventListener('click', () => { page--; render(); });
                    next?.addEventListener('click', () => { page++; render(); });
                    render();
                });
            </script>
        @endif
    </section>

    {{-- ============ CV TEMPLATES (auto-scrolling) ============ --}}
    <section id="mau-cv" class="scroll-mt-20 overflow-hidden py-16">
        <div class="mx-auto flex max-w-6xl flex-col justify-between gap-4 px-4 sm:flex-row sm:items-end sm:px-6">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">Mẫu CV chuẩn cho mọi ngành nghề &amp; thị trường</h2>
                <p class="mt-3 max-w-2xl text-slate-500">Chọn mẫu CV chuyên nghiệp, dễ đọc, thân thiện với ATS (hệ thống sàng lọc hồ sơ). Hỗ trợ CV tiếng Trung, tiếng Nhật và lao động phổ thông. Thông tin tự lấy từ hồ sơ, tải PDF miễn phí.</p>
            </div>
            <a href="{{ route('cv-templates.index') }}" class="shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-800">Xem tất cả &rarr;</a>
        </div>

        <div class="cv-marquee group relative mx-auto mt-8 max-w-6xl [mask-image:linear-gradient(to_right,transparent,black_4%,black_96%,transparent)]">
            <div class="cv-marquee-track flex w-max gap-5 py-2 group-hover:[animation-play-state:paused]">
                @foreach ([false, true] as $isCopy)
                    @foreach ($cvTemplates as $key => $template)
                        @php $cv->template = $key; @endphp
                        <a href="{{ route('cv-templates.show', $key) }}" @if ($isCopy) aria-hidden="true" tabindex="-1" @endif
                           class="flex w-64 shrink-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-blue-300 hover:shadow-lg">
                            <div class="relative h-48 overflow-hidden bg-slate-100 px-5 pt-5">
                                <div class="pointer-events-none mx-auto w-[210mm] bg-white shadow [zoom:0.27]" style="--accent: {{ $cv->accent_color }}" aria-hidden="true">
                                    @include('candidate.cvs.templates.'.$key)
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-4">
                                <h3 class="font-semibold text-slate-900">{{ $template['name'] }}</h3>
                                <p class="mt-1 line-clamp-2 flex-1 text-sm text-slate-500">{{ $template['description'] }}</p>
                                <div class="mt-3 flex flex-wrap gap-1.5 text-xs text-slate-600">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1">文A {{ $template['language'] ?? 'Tiếng Việt' }}</span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ ! empty($template['photo']) ? 'Có ô ảnh' : 'Không cần ảnh' }}</span>
                                    @if ($template['ats'])
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700">ATS</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ auth()->user()?->isCandidate() ? route('candidate.cvs.create') : route('cv-templates.index') }}" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-8 py-3 font-semibold text-white shadow-lg shadow-blue-600/30 hover:bg-blue-700">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.9 4.6 2.5 2.5M4 20l1-4L16 5l3 3L8 19l-4 1Z"/></svg>
                Tạo CV ngay
            </a>
        </div>

        <style>
            @keyframes cv-marquee { to { transform: translateX(calc(-50% - 0.625rem)); } }
            .cv-marquee-track { animation: cv-marquee 45s linear infinite; }
            @media (prefers-reduced-motion: reduce) {
                .cv-marquee-track { animation: none; }
                .cv-marquee { overflow-x: auto; }
            }
        </style>
    </section>

    {{-- ============ FEATURED EVENTS & ARTICLES (posted by admins) ============ --}}
    @if ($featuredEvents->isNotEmpty() || $featuredArticles->isNotEmpty())
        <section id="noi-dung" class="scroll-mt-20 bg-slate-50 py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">Nội dung nổi bật</h2>
                <p class="mt-2 text-slate-500">Sự kiện tuyển dụng và bài viết hướng nghiệp được chọn lọc cho bạn.</p>

                @foreach ([
                    ['Sự kiện nổi bật', $featuredEvents, 'event', 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z'],
                    ['Bài viết nổi bật', $featuredArticles, 'article', 'M9 12h6m-6 4h6M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z'],
                ] as [$heading, $posts, $type, $icon])
                    @continue($posts->isEmpty())
                    <div class="mt-10">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center gap-3 text-lg font-semibold text-slate-900">
                                <span class="grid size-8 place-items-center rounded-lg bg-blue-100 text-blue-600">
                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                </span>
                                {{ $heading }}
                            </h3>
                            <a href="{{ route('posts.index', ['type' => $type]) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">Xem tất cả &rarr;</a>
                        </div>

                        <div class="mt-5 grid gap-5 lg:grid-cols-[1.25fr_1fr]">
                            {{-- Lead post: large cover with text overlay --}}
                            @php $lead = $posts->first(); @endphp
                            <article class="group relative min-h-80 overflow-hidden rounded-2xl bg-slate-900 lg:row-span-2">
                                @include('posts.partials.cover', ['post' => $lead, 'class' => 'absolute inset-0 size-full transition duration-500 group-hover:scale-105'])
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/30 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                                    @if ($lead->category)
                                        <span class="rounded-full bg-slate-950/70 px-3 py-1 text-xs font-semibold">{{ $lead->category }}</span>
                                    @endif
                                    <h4 class="mt-3 text-xl font-bold">
                                        <a href="{{ route('posts.show', $lead) }}" class="after:absolute after:inset-0">{{ $lead->title }}</a>
                                    </h4>
                                    <p class="mt-2 line-clamp-2 text-sm text-slate-200">{{ $lead->excerpt }}</p>
                                    <div class="mt-3 flex items-center justify-between text-sm">
                                        <span>📅 {{ $lead->displayDate() }}</span>
                                        <span class="font-semibold">Xem chi tiết &rarr;</span>
                                    </div>
                                </div>
                            </article>

                            @foreach ($posts->skip(1) as $post)
                                <article class="group relative flex overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:shadow-lg">
                                    @include('posts.partials.cover', ['class' => 'w-2/5 shrink-0 self-stretch'])
                                    <div class="flex min-w-0 flex-1 flex-col p-5">
                                        @if ($post->category)
                                            <p class="text-xs font-semibold text-blue-600">{{ $post->category }}</p>
                                        @endif
                                        <h4 class="mt-1 line-clamp-2 font-semibold text-slate-900 group-hover:text-blue-700">
                                            <a href="{{ route('posts.show', $post) }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
                                        </h4>
                                        <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $post->excerpt }}</p>
                                        <p class="mt-auto pt-3 text-xs text-slate-500">📅 {{ $post->displayDate() }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============ EMPLOYER CTA ============ --}}
    <section id="nha-tuyen-dung" class="mx-auto max-w-7xl scroll-mt-20 px-4 py-20 sm:px-6">
        <div class="relative overflow-hidden rounded-3xl bg-slate-900 px-6 py-14 sm:px-14">
            <div class="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-emerald-500/30 blur-3xl"></div>
            <div class="relative grid items-center gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <p class="text-sm font-semibold tracking-wider text-emerald-400 uppercase">Dành cho nhà tuyển dụng</p>
                    <h2 class="mt-2 text-3xl font-bold text-white">Tìm đúng ứng viên, nhanh hơn bao giờ hết</h2>
                    <p class="mt-3 max-w-xl text-slate-300">Đăng tin miễn phí, tiếp cận hàng trăm nghìn ứng viên và quản lý toàn bộ quy trình tuyển dụng trên một bảng điều khiển.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                    <a href="{{ route('register', ['role' => 'employer']) }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-6 py-3 font-semibold text-white hover:bg-emerald-400">Đăng tin tuyển dụng</a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 px-6 py-3 font-semibold text-white hover:bg-white/10">Đăng nhập NTD</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section id="hoi-dap" class="scroll-mt-20 bg-slate-50 py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">Câu hỏi thường gặp</h2>
            <div class="mt-10 space-y-3">
                @foreach ($faqs as $faq)
                    <details class="group rounded-2xl border border-slate-200 bg-white p-5 open:shadow-md">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                            {{ $faq['q'] }}
                            <svg class="size-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ SIGN-UP CTA (guests only) ============ --}}
    @guest
        <section class="mx-auto max-w-7xl px-4 py-20 text-center sm:px-6">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Sẵn sàng cho công việc tiếp theo?</h2>
            <p class="mx-auto mt-3 max-w-xl text-slate-600">Tạo tài khoản miễn phí để lưu việc làm, ứng tuyển nhanh và nhận gợi ý phù hợp.</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="btn-primary px-6 py-3">Đăng ký ứng viên</a>
                <a href="{{ route('login') }}" class="btn-secondary px-6 py-3">Tôi đã có tài khoản</a>
            </div>
        </section>
    @endguest
@endsection
