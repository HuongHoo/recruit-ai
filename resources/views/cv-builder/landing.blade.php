@extends('layouts.public')
@section('title', 'Tạo CV chuẩn ATS')

@php
    $firstKey = array_key_first($templates);
    $faqs = [
        ['Tôi có cần tài khoản không?', 'Bạn xem mẫu CV thoải mái mà không cần đăng nhập. Khi bắt đầu soạn, bạn cần tài khoản ứng viên (miễn phí) để CV được lưu và chỉnh sửa lại sau.'],
        ['Tôi có thể nhập file nào?', 'Bạn có thể tải lên CV có sẵn dạng PDF, DOC hoặc DOCX để lưu trữ và nộp hồ sơ. CV tạo trực tuyến được xuất ra PDF.'],
        ['CV có bị tải lên trước khi đăng nhập không?', 'Không. Hệ thống chỉ lưu CV sau khi bạn đăng nhập và bấm Lưu.'],
        ['Dùng thử có miễn phí không?', 'Có. Tạo CV, đổi mẫu, xem điểm ATS và tải PDF đều miễn phí.'],
        ['Một CV mạnh nên có những gì?', 'Thông tin liên hệ đầy đủ, phần tóm tắt 2–4 câu, kinh nghiệm có số liệu cụ thể, học vấn và 5–10 kỹ năng khớp với tin tuyển dụng.'],
        ['CV nên dài bao nhiêu?', 'Với dưới 5 năm kinh nghiệm, 1 trang là đủ. Nhiều kinh nghiệm hơn có thể dài 2 trang.'],
        ['Tôi có thể tạo nhiều bản cho từng việc không?', 'Có. Mỗi tài khoản lưu được tối đa '.config('recruitment.cv.max_per_user').' CV. Mỗi CV có tên, mẫu, màu và phần tóm tắt riêng.'],
        ['Có nên dùng AI cho CV không?', 'AI giúp so khớp CV với từng tin tuyển dụng và chỉ ra yêu cầu còn thiếu. Bạn vẫn nên tự kiểm tra để nội dung đúng với kinh nghiệm thật.'],
    ];
    $cv->template = 'ats';
@endphp

@section('content')
    {{-- ============ HERO ============ --}}
    <section class="mx-auto max-w-6xl px-4 pt-16 text-center sm:px-6">
        <h1 class="text-4xl leading-tight font-extrabold tracking-tight text-slate-900 sm:text-5xl">
            Vượt qua vòng sàng lọc CV<br>
            <span class="relative inline-block text-blue-700">của AI và ATS.
                <svg class="absolute -bottom-2 left-0 w-full text-blue-600" viewBox="0 0 300 12" preserveAspectRatio="none" aria-hidden="true"><path d="M2 9C80 2 220 2 298 8" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            </span>
        </h1>
        <p class="mx-auto mt-6 max-w-xl text-slate-600">AI và ATS lọc CV trước khi nhà tuyển dụng đọc. {{ config('app.name') }} <b class="text-slate-900">chấm điểm ATS và so khớp CV với từng JD</b>, để bạn biết cần sửa gì trước khi nộp.</p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <a href="{{ $startUrl }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-blue-600 px-8 py-3 font-semibold text-white shadow-lg shadow-blue-600/30 hover:bg-blue-700">Bắt đầu tạo CV &rarr;</a>
            <a href="#mau-cv" class="inline-flex items-center justify-center rounded-full border border-slate-300 px-8 py-3 font-semibold text-slate-800 hover:bg-slate-50">Xem mẫu CV</a>
        </div>
        <p class="mt-4 flex flex-wrap justify-center gap-x-5 gap-y-1 text-xs text-slate-500">
            <span>✓ Mẫu chuẩn ATS</span><span>✓ Tải CV có sẵn (PDF/DOCX)</span><span>✓ Tải PDF</span>
        </p>

        {{-- Product mock: the real builder layout with a live ATS template --}}
        <div class="relative mt-12 rounded-3xl bg-gradient-to-br from-indigo-100 via-violet-100 to-blue-100 p-4 sm:p-10">
            <div class="overflow-hidden rounded-xl bg-white text-left shadow-2xl shadow-indigo-900/20 ring-1 ring-slate-200">
                <div class="flex items-center gap-1.5 border-b border-slate-100 px-4 py-2.5">
                    <span class="size-2.5 rounded-full bg-red-400"></span><span class="size-2.5 rounded-full bg-amber-400"></span><span class="size-2.5 rounded-full bg-emerald-400"></span>
                </div>
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-2 text-[11px] text-slate-600">
                    <span class="flex-1">&larr; Quản lý CV / <b>CV chuẩn ATS</b></span>
                    <span class="rounded border border-slate-200 px-2 py-1">Tải CV lên</span>
                    <span class="rounded border border-emerald-200 bg-emerald-50 px-2 py-1 font-semibold text-emerald-700">86/100 Điểm ATS</span>
                    <span class="rounded border border-slate-200 px-2 py-1">Xem trước</span>
                    <span class="rounded bg-blue-600 px-2 py-1 font-semibold text-white">Tải xuống</span>
                </div>
                <div class="grid md:grid-cols-2">
                    <div class="p-5">
                        <p class="text-[11px] text-slate-500"><b class="text-slate-800">Tổng quan CV</b> · Đã điền 4/5 mục · <span class="text-amber-600">Còn 1 mục cần bổ sung</span></p>
                        <ol class="mt-4 flex items-start justify-between text-[10px] text-slate-500">
                            @foreach (['Thông tin cá nhân', 'Tóm tắt', 'Kinh nghiệm', 'Học vấn', 'Kỹ năng', 'Hoàn tất'] as $i => $label)
                                <li class="flex w-14 flex-col items-center gap-1 text-center">
                                    <span @class(['grid size-6 place-items-center rounded-full text-[10px] font-bold', 'bg-blue-600 text-white' => $i < 3, 'border border-slate-300' => $i >= 3])>{{ $i < 2 ? '✓' : $i + 1 }}</span>
                                    <span @class(['text-blue-700 font-semibold' => $i === 2])>{{ $label }}</span>
                                </li>
                            @endforeach
                        </ol>
                        <div class="mt-5 space-y-3 rounded-lg border border-slate-200 p-4 text-[11px]">
                            <p class="font-semibold text-slate-800">Kinh nghiệm 1 · {{ $experiences[0]->position }}</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div><p class="text-slate-500">Tên công ty</p><p class="mt-1 rounded border border-slate-200 px-2 py-1.5">{{ $experiences[0]->company }}</p></div>
                                <div><p class="text-slate-500">Vị trí</p><p class="mt-1 rounded border border-slate-200 px-2 py-1.5">{{ $experiences[0]->position }}</p></div>
                            </div>
                            <div><p class="text-slate-500">Mô tả công việc</p><p class="mt-1 h-16 overflow-hidden rounded border border-slate-200 px-2 py-1.5 leading-relaxed">{{ $experiences[0]->description }}</p></div>
                            <div class="flex justify-end"><span class="rounded bg-blue-600 px-3 py-1 font-semibold text-white">Tiếp &rarr;</span></div>
                        </div>
                    </div>
                    <div class="hidden h-80 overflow-hidden bg-slate-50 px-6 pt-5 md:block">
                        <div class="pointer-events-none mx-auto w-[210mm] bg-white shadow-lg [zoom:0.45]" style="--accent: {{ $cv->accent_color }}" aria-hidden="true">
                            @include('candidate.cvs.templates.ats')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FEATURE STRIP ============ --}}
    <section class="mx-auto mt-12 grid max-w-6xl gap-4 px-4 sm:grid-cols-3 sm:px-6">
        @foreach ([
            ['Soạn theo từng bước', 'Thông tin cá nhân, tóm tắt, kinh nghiệm, học vấn, kỹ năng — mỗi bước có hướng dẫn rõ ràng.'],
            ['Xem trước trực tiếp', 'Gõ đến đâu, CV cập nhật đến đó trên đúng mẫu bạn chọn. Đổi mẫu, đổi màu bất cứ lúc nào.'],
            ['So khớp với JD bằng AI', 'Chọn tin tuyển dụng, AI chỉ ra yêu cầu nào đã đáp ứng, còn thiếu và cần sửa gì.'],
        ] as [$title, $text])
            <div class="rounded-2xl border border-slate-200 p-5">
                <p class="font-semibold text-slate-900">{{ $title }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $text }}</p>
            </div>
        @endforeach
    </section>

    {{-- ============ EMPLOYERS ============ --}}
    @if ($companies->isNotEmpty())
        <section class="mt-20 overflow-hidden text-center">
            <p class="text-xs text-slate-500">Nhà tuyển dụng đang tuyển trên {{ config('app.name') }}</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($companyCount, 0, ',', '.') }} doanh nghiệp đang tuyển dụng trên {{ config('app.name') }}</h2>
            <div class="logo-marquee group mx-auto mt-8 max-w-6xl [mask-image:linear-gradient(to_right,transparent,black_6%,black_94%,transparent)]">
                <div class="logo-marquee-track flex w-max gap-4 group-hover:[animation-play-state:paused]">
                    @foreach ([false, true] as $isCopy)
                        @foreach ($companies as $company)
                            <a href="{{ route('jobs.index', ['keyword' => $company->name]) }}" @if ($isCopy) aria-hidden="true" tabindex="-1" @endif class="flex w-56 shrink-0 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:border-blue-300">
                                <img src="{{ $company->logoUrl() }}" alt="" class="size-9 shrink-0 rounded-lg object-contain">
                                <span class="truncate text-sm font-semibold text-slate-800">{{ $company->name }}</span>
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </div>
            <a href="{{ route('jobs.index') }}" class="mt-6 inline-block text-sm font-semibold text-blue-700 hover:text-blue-800">Xem việc làm đang tuyển &rarr;</a>
        </section>
    @endif

    {{-- ============ TEMPLATES (tabs) ============ --}}
    <section id="mau-cv" class="mx-auto mt-24 max-w-6xl scroll-mt-20 px-4 sm:px-6">
        <div class="text-center">
            <h2 class="text-3xl font-semibold tracking-tight text-slate-900">Mẫu CV cho từng thị trường tuyển dụng</h2>
            <p class="mt-2 text-slate-500">Mỗi mẫu theo đúng bố cục và các mục mà nhà tuyển dụng ở thị trường đó mong đợi.</p>
        </div>
        <div class="mt-6 flex flex-wrap justify-center gap-2" role="tablist">
            @foreach ($templates as $key => $template)
                <button type="button" role="tab" data-tab="{{ $key }}" aria-selected="{{ $key === $firstKey ? 'true' : 'false' }}"
                        class="rounded-full border border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-700 hover:border-blue-400 aria-selected:border-blue-600 aria-selected:bg-blue-600 aria-selected:text-white">{{ $template['name'] }}</button>
            @endforeach
        </div>

        @foreach ($templates as $key => $template)
            @php $cv->template = $key; @endphp
            <div data-panel="{{ $key }}" role="tabpanel" class="mt-8 grid items-center gap-8 md:grid-cols-[1.2fr_1fr]" @if ($key !== $firstKey) hidden @endif>
                <div class="h-96 overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-200 via-violet-100 to-blue-200 px-10 pt-10">
                    <div class="pointer-events-none mx-auto w-[210mm] bg-white shadow-2xl [zoom:0.42]" style="--accent: {{ $cv->accent_color }}" aria-hidden="true">
                        @include('candidate.cvs.templates.'.$key)
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-semibold text-slate-900">{{ $template['name'] }}</h3>
                    <p class="mt-3 text-slate-700">{{ $template['description'] }}</p>
                    <p class="mt-3 flex gap-2 text-sm text-slate-500"><span aria-hidden="true">◎</span>{{ $template['suitable'] }}</p>
                    <p class="mt-5 text-xs font-semibold text-slate-700">Mục bắt buộc</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        @foreach (['Tóm tắt', 'Kinh nghiệm làm việc', 'Học vấn', 'Kỹ năng'] as $section)
                            <span class="rounded-full bg-blue-50 px-3 py-1 font-medium text-blue-700">{{ $section }}</span>
                        @endforeach
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-600">
                        <span class="rounded-full border border-slate-200 px-3 py-1">文A {{ $template['language'] ?? 'Tiếng Việt' }}</span>
                        <span class="rounded-full border border-slate-200 px-3 py-1">{{ ! empty($template['photo']) ? 'Có ô ảnh' : 'Không cần ảnh' }}</span>
                        @if ($template['ats']) <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700">Thân thiện ATS</span> @endif
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ $startUrl === route('candidate.cvs.create') ? route('candidate.cvs.create', ['template' => $key]) : $startUrl }}" class="btn-primary rounded-full px-6">Dùng mẫu này</a>
                        <a href="{{ route('cv-templates.show', $key) }}" class="btn-secondary rounded-full px-6">Xem chi tiết mẫu</a>
                    </div>
                </div>
            </div>
        @endforeach
        <p class="mt-8 text-center"><a href="{{ route('cv-templates.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">Xem tất cả mẫu CV &rarr;</a></p>
    </section>

    {{-- ============ ATS / JD MATCH ILLUSTRATION (sample figures) ============ --}}
    @php
        $atsRows = [['Thông tin liên hệ đầy đủ', 12, 12], ['Tóm tắt nghề nghiệp đúng độ dài', 6, 12], ['Kinh nghiệm làm việc có đủ thông tin', 12, 12], ['Thành tích có số liệu', 5, 10], ['Kỹ năng đủ để khớp từ khóa', 4, 10], ['Mô tả công việc theo gạch đầu dòng', 8, 8]];
        $atsGroups = [
            ['Quan trọng', [['Tóm tắt nghề nghiệp đúng độ dài', 'Tóm tắt hiện 18 từ. Nên giữ trong khoảng 30–150 từ: nêu vị trí, số năm kinh nghiệm và 2–3 điểm mạnh.', 6, 6, true]]],
            ['Nên bổ sung', [
                ['Thành tích có số liệu', 'Chỉ một vài mô tả có số liệu. Thêm kết quả đo được vào từng công việc: tăng bao nhiêu %, tiết kiệm bao lâu, phụ trách bao nhiêu người.', 5, 5, false],
                ['Kỹ năng đủ để khớp từ khóa', 'Mới có 3 kỹ năng. Thêm ít nhất 5 kỹ năng lấy đúng từ khóa trong tin tuyển dụng.', 4, 6, false],
            ]],
        ];

        // Radar chart: CV vs JD on 5 criteria (0–5), centre (110,105), radius 70.
        $axes = ['Bán hàng B2B', 'Quản lý đại lý', 'Kỹ năng CRM', 'Đàm phán', 'Tiếng Anh'];
        $cvScores = [4, 4, 2.5, 4, 2];
        $jdScores = [5, 4, 4, 4, 3.5];
        $point = fn (int $i, float $value): string => round(110 + 70 * $value / 5 * sin(2 * M_PI * $i / 5), 1).','.round(105 - 70 * $value / 5 * cos(2 * M_PI * $i / 5), 1);
        $polygon = fn (array $values): string => collect($values)->map(fn ($v, $i) => $point($i, $v))->implode(' ');

        $requirements = [
            ['Sử dụng thành thạo phần mềm CRM', 'clarify'],
            ['Kỹ năng đàm phán và thuyết phục tốt', 'met'],
            ['Tiếng Anh giao tiếp', 'unknown'],
        ];
        $statusStyles = [
            'met' => ['✓ Đáp ứng', 'border-l-emerald-500', 'bg-emerald-50 text-emerald-700'],
            'clarify' => ['! Cần làm rõ', 'border-l-amber-500', 'bg-amber-50 text-amber-700'],
            'missing' => ['✕ Chưa đáp ứng', 'border-l-red-500', 'bg-red-50 text-red-700'],
            'unknown' => ['? Chưa thấy', 'border-l-slate-300', 'bg-slate-100 text-slate-600'],
        ];
    @endphp
    <section class="mx-auto mt-24 max-w-5xl px-4 sm:px-6" data-insight>
        <div class="text-center">
            <h2 class="text-3xl font-semibold tracking-tight text-slate-900">Biết CV của bạn đứng ở đâu</h2>
            <p class="mx-auto mt-2 max-w-lg text-slate-500">Điểm ATS cho biết CV đọc được đến đâu. So khớp JD cho biết bạn hợp với vị trí đến đâu — và cần sửa gì.</p>
            <div class="mt-6 inline-flex gap-2">
                @foreach (['ats' => 'Chấm điểm ATS', 'jd' => 'So khớp với JD'] as $key => $label)
                    <button type="button" data-insight-tab="{{ $key }}" aria-selected="{{ $key === 'ats' ? 'true' : 'false' }}"
                            class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-blue-400 aria-selected:border-blue-600 aria-selected:bg-blue-600 aria-selected:text-white aria-selected:ring-4 aria-selected:ring-blue-600/20">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-2xl shadow-slate-900/10">
            <div class="flex gap-1.5 border-b border-slate-200 bg-white px-4 py-3">
                <span class="size-2.5 rounded-full bg-red-400"></span><span class="size-2.5 rounded-full bg-amber-400"></span><span class="size-2.5 rounded-full bg-emerald-400"></span>
            </div>

            {{-- ---------- Tab: ATS score ---------- --}}
            <div data-insight-panel="ats" class="grid gap-5 p-5 sm:p-7 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="font-semibold text-slate-900">Cải thiện CV</p>
                    <div class="mt-4 flex items-center gap-5 border-b border-slate-100 pb-5">
                        <div class="grid size-24 shrink-0 place-items-center rounded-full" style="background: conic-gradient(#16a34a 317deg, #dbeafe 0)">
                            <span class="grid size-[5rem] place-items-center rounded-full bg-white text-center leading-none">
                                <span><b class="block text-3xl text-slate-900">88</b><span class="text-xs text-slate-500">/100</span></span>
                            </span>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p class="text-xs text-slate-500">ATS</p>
                            <span class="inline-block rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Rất tốt</span>
                            <p class="flex items-center gap-2 text-xs text-slate-500"><span class="grid size-8 place-items-center rounded-full bg-emerald-800 text-xs font-bold text-white">74</span> Điểm hồ sơ</p>
                        </div>
                    </div>
                    <ul class="mt-4 space-y-3.5 text-sm">
                        @foreach ($atsRows as [$label, $got, $max])
                            <li>
                                <div class="flex justify-between text-slate-700"><span>{{ $label }}</span><span class="text-slate-500">{{ $got }}/{{ $max }}</span></div>
                                <div class="mt-1.5 h-1.5 rounded-full bg-slate-100"><div @class(['h-full rounded-full', 'bg-emerald-600' => $got === $max, 'bg-amber-400' => $got < $max]) style="width: {{ round($got / $max * 100) }}%"></div></div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="divide-y divide-slate-200 self-start rounded-xl border border-slate-200 bg-white">
                    @foreach ($atsGroups as [$group, $items])
                        <details open class="group px-5 py-4">
                            <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                                <span>{{ $group }} <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ count($items) }}</span></span>
                                <span class="text-slate-400 transition group-open:rotate-180">⌃</span>
                            </summary>
                            <div class="mt-3 divide-y divide-slate-100">
                                @foreach ($items as [$title, $text, $profilePoints, $atsPoints, $action])
                                    <div class="flex gap-3 py-3 first:pt-0">
                                        <span class="mt-0.5 grid size-4 shrink-0 place-items-center rounded-full border-2 border-amber-500 text-[9px] font-bold text-amber-600">!</span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
                                            <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $text }}</p>
                                            <div class="mt-2 flex items-end justify-between gap-2">
                                                <span class="flex gap-1.5 text-[11px] font-semibold">
                                                    <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-emerald-700">+{{ $profilePoints }} điểm</span>
                                                    <span class="rounded bg-blue-50 px-1.5 py-0.5 text-blue-700">+{{ $atsPoints }} ATS</span>
                                                </span>
                                                @if ($action)
                                                    <span class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">✎ Thêm ngay</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                    <details class="group px-5 py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-slate-700 [&::-webkit-details-marker]:hidden">
                            <span class="text-emerald-700">✓ <span class="text-slate-700">Đã đạt (7)</span></span>
                            <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                        </summary>
                        <ul class="mt-3 space-y-1.5 text-xs text-slate-500">
                            @foreach (['Có họ tên và email', 'Có số điện thoại', 'Có địa chỉ', 'Có vị trí ứng tuyển', 'Có kinh nghiệm làm việc', 'Có học vấn', 'Dùng mẫu thân thiện ATS'] as $done)
                                <li>✓ {{ $done }}</li>
                            @endforeach
                        </ul>
                    </details>
                </div>
            </div>

            {{-- ---------- Tab: JD match ---------- --}}
            <div data-insight-panel="jd" class="grid gap-5 p-5 sm:p-7 md:grid-cols-[1fr_1.5fr]" hidden>
                <div class="self-start rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="flex items-center gap-2 font-semibold text-slate-900"><span class="rounded-md bg-blue-50 px-1.5 py-0.5 text-[11px] text-blue-700">✦ AI</span> AI đánh giá độ phù hợp với JD</p>
                    <p class="mt-1 text-xs text-slate-500">Dán tin tuyển dụng: AI chấm điểm phù hợp, chỉ ra yêu cầu CV đã đáp ứng, còn thiếu và gợi ý sửa.</p>
                    <p class="mt-4 text-xs font-semibold text-slate-700">Mô tả công việc</p>
                    <div class="mt-1.5 rounded-lg border border-slate-200 p-3 text-xs leading-relaxed text-slate-700">
                        Nhân viên kinh doanh B2B<br>- Tối thiểu 2 năm kinh nghiệm bán hàng B2B<br>- Quản lý và phát triển hệ thống đại lý<br>- Sử dụng thành thạo phần mềm CRM<br>- Kỹ năng đàm phán và thuyết phục tốt<br>- Tiếng Anh giao tiếp
                    </div>
                    <p class="mt-2 flex justify-between text-[11px] text-slate-400"><span>Sao chép toàn bộ tin tuyển dụng (mô tả, yêu cầu, kỹ năng) và dán vào đây.</span><span class="text-emerald-600">201</span></p>
                    <div class="mt-4 flex justify-end gap-2 text-xs font-semibold">
                        <span class="rounded-lg bg-slate-100 px-3 py-2.5 text-slate-600">Hủy</span>
                        <span class="rounded-lg bg-blue-600 px-3 py-2.5 text-white">✦ AI đánh giá ngay</span>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
                            <p class="text-sm font-semibold text-slate-800">Mức độ phù hợp</p>
                            <div class="mx-auto mt-3 grid size-24 place-items-center rounded-full" style="background: conic-gradient(#2563eb 281deg, #dbeafe 0)">
                                <span class="grid size-[5rem] place-items-center rounded-full bg-white text-3xl font-bold text-slate-900">78</span>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-blue-700">● Khá phù hợp</p>
                            <p class="mt-1 text-xs text-slate-500">3 / 5 yêu cầu nổi bật đáp ứng</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-semibold text-slate-800">Tiêu chí chung</p>
                            <svg viewBox="0 0 220 200" class="mt-1 w-full" role="img" aria-label="Biểu đồ so sánh CV và JD theo 5 tiêu chí">
                                @foreach ([1, 0.75, 0.5, 0.25] as $ring)
                                    <polygon points="{{ $polygon(array_fill(0, 5, 5 * $ring)) }}" fill="none" stroke="#e2e8f0"/>
                                @endforeach
                                @foreach ($axes as $i => $axis)
                                    <line x1="110" y1="105" x2="{{ explode(',', $point($i, 5))[0] }}" y2="{{ explode(',', $point($i, 5))[1] }}" stroke="#e2e8f0"/>
                                    @php [$lx, $ly] = explode(',', $point($i, 6.3)); @endphp
                                    <text x="{{ $lx }}" y="{{ $ly }}" font-size="8" fill="#475569" text-anchor="middle" dominant-baseline="middle">{{ $axis }}</text>
                                @endforeach
                                <polygon points="{{ $polygon($jdScores) }}" fill="#2563eb" fill-opacity="0.12" stroke="#2563eb" stroke-width="1.5"/>
                                <polygon points="{{ $polygon($cvScores) }}" fill="#16a34a" fill-opacity="0.3" stroke="#16a34a" stroke-width="1.5"/>
                            </svg>
                            <p class="flex justify-center gap-4 text-[11px] text-slate-500"><span><b class="text-emerald-600">●</b> CV</span><span><b class="text-blue-600">●</b> JD</span></p>
                            <p class="text-center text-[11px] text-slate-400">Theo các yêu cầu trong JD</p>
                        </div>
                    </div>

                    <div>
                        <p class="font-semibold text-slate-900">Đối chiếu yêu cầu công việc</p>
                        <div class="mt-2 flex flex-wrap gap-1.5 text-[11px] font-semibold">
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700">✓ Đáp ứng (3)</span>
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700">! Cần làm rõ (1)</span>
                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-red-700">✕ Chưa đáp ứng (0)</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600">? Chưa thấy (1)</span>
                        </div>
                        <ul class="mt-3 space-y-2">
                            @foreach ($requirements as [$text, $status])
                                @php [$label, $border, $badge] = $statusStyles[$status]; @endphp
                                <li class="{{ $border }} flex items-center justify-between gap-3 rounded-lg border border-l-4 border-slate-200 bg-white px-4 py-3 text-sm">
                                    <span class="text-slate-800">{{ $text }}</span>
                                    <span class="{{ $badge }} shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold">{{ $label }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <p class="font-semibold text-slate-900">Ưu tiên cải thiện CV</p>
                        <ol class="mt-3 space-y-2">
                            @foreach ([
                                ['Nêu rõ phần mềm CRM đã dùng', 'Ghi tên phần mềm và việc bạn làm với nó trong mục Kinh nghiệm làm việc.'],
                                ['Bổ sung trình độ tiếng Anh', 'Thêm mục Ngoại ngữ với trình độ hoặc chứng chỉ cụ thể.'],
                            ] as $i => [$title, $text])
                                <li class="flex gap-3 rounded-xl bg-slate-100 p-4">
                                    <span class="grid size-6 shrink-0 place-items-center rounded-full bg-blue-600 text-xs font-bold text-white">{{ $i + 1 }}</span>
                                    <span><b class="block text-sm text-slate-900">{{ $title }}</b><span class="text-xs text-slate-500">{{ $text }}</span></span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <p class="mt-3 text-center text-xs text-slate-400">Số liệu minh họa</p>
    </section>

    {{-- ============ WHY ============ --}}
    <section class="mx-auto mt-24 max-w-5xl px-4 sm:px-6">
        <div class="rounded-3xl bg-gradient-to-br from-slate-950 via-indigo-950 to-blue-950 px-6 py-12 text-center text-white sm:px-12">
            <p class="text-xs font-semibold text-blue-300">● Vì sao sàng lọc AI quan trọng</p>
            <h2 class="mx-auto mt-3 max-w-xl text-2xl font-semibold sm:text-3xl">Trước khi đến tay người tuyển dụng, CV của bạn được AI đọc trước.</h2>
            <p class="mt-3 text-sm text-slate-300">Nếu hệ thống không đọc được CV, nhà tuyển dụng có thể không bao giờ thấy nó.</p>
            <div class="mt-10 grid gap-8 sm:grid-cols-3">
                @foreach ([
                    ['Nhà tuyển dụng dễ đọc nhanh', 'Mục gọn và kết quả nổi bật giúp điểm mạnh của bạn dễ được nhận ra.'],
                    ['Sẵn sàng để AI hỗ trợ tốt hơn', 'Mục được định nghĩa rõ giúp AI so khớp với JD và gợi ý chính xác hơn.'],
                    ['Tương thích với ATS', 'Tiêu đề chuẩn và định dạng sạch giúp giảm lỗi phân tích — không cần nhồi từ khóa.'],
                ] as [$title, $text])
                    <div>
                        <span class="mx-auto grid size-10 place-items-center rounded-lg border border-white/20 bg-white/5">◎</span>
                        <p class="mt-3 font-semibold">{{ $title }}</p>
                        <p class="mt-1 text-sm text-slate-300">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ 3 STEPS ============ --}}
    <section class="mx-auto mt-24 max-w-5xl px-4 sm:px-6">
        <h2 class="text-center text-3xl font-semibold tracking-tight text-slate-900">Ba bước để có CV hoàn chỉnh</h2>
        <div class="mt-10 grid gap-8 sm:grid-cols-3">
            @foreach ([
                ['Chọn cách bắt đầu', 'Chọn một mẫu phù hợp thị trường bạn ứng tuyển. Thông tin có sẵn trong hồ sơ được tự điền vào.'],
                ['Hoàn thiện từng mục', 'Mỗi bước có hướng dẫn và gợi ý để mô tả rõ công việc, kết quả và kỹ năng.'],
                ['Kiểm tra và tải PDF', 'Xem điểm ATS, sửa theo gợi ý rồi tải CV dạng PDF. CV được lưu trong tài khoản để chỉnh lại khi cần.'],
            ] as $i => [$title, $text])
                <div class="border-t border-slate-200 pt-5">
                    <p class="text-5xl font-bold text-slate-900">0{{ $i + 1 }}</p>
                    <p class="mt-4 font-semibold text-slate-900">{{ $title }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section class="mx-auto mt-24 max-w-2xl px-4 sm:px-6">
        <h2 class="text-center text-3xl font-semibold tracking-tight text-slate-900">Câu hỏi thường gặp</h2>
        <div class="mt-8 space-y-2">
            @foreach ($faqs as [$q, $a])
                <details class="group rounded-xl bg-slate-100 px-5 py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                        {{ $q }}<span class="text-xl leading-none text-slate-400 transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="mx-auto my-24 max-w-5xl px-4 sm:px-6">
        <div class="rounded-3xl bg-gradient-to-br from-indigo-100 via-violet-100 to-blue-100 px-6 py-14 text-center">
            <h2 class="text-2xl font-semibold text-slate-900 sm:text-3xl">Bắt đầu CV tiếp theo của bạn</h2>
            <p class="mx-auto mt-3 max-w-md text-sm text-slate-600">Chọn mẫu, điền từng bước và tải PDF. Bạn chỉ cần đăng nhập khi bắt đầu soạn.</p>
            <a href="{{ $startUrl }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-blue-600 px-7 py-2.5 font-semibold text-white hover:bg-blue-700">Bắt đầu tạo CV &rarr;</a>
        </div>
    </section>

    <style>
        @keyframes logo-marquee { to { transform: translateX(calc(-50% - 0.5rem)); } }
        .logo-marquee-track { animation: logo-marquee 40s linear infinite; }
        @media (prefers-reduced-motion: reduce) { .logo-marquee-track { animation: none; } .logo-marquee { overflow-x: auto; } }
    </style>
    <script>
        document.querySelectorAll('[data-insight-tab]').forEach((tab) => tab.addEventListener('click', () => {
            document.querySelectorAll('[data-insight-tab]').forEach((t) => t.setAttribute('aria-selected', t === tab ? 'true' : 'false'));
            document.querySelectorAll('[data-insight-panel]').forEach((p) => { p.hidden = p.dataset.insightPanel !== tab.dataset.insightTab; });
        }));
        document.querySelectorAll('[data-tab]').forEach((tab) => tab.addEventListener('click', () => {
            document.querySelectorAll('[data-tab]').forEach((t) => t.setAttribute('aria-selected', t === tab ? 'true' : 'false'));
            document.querySelectorAll('[data-panel]').forEach((p) => { p.hidden = p.dataset.panel !== tab.dataset.tab; });
        }));
    </script>
@endsection
