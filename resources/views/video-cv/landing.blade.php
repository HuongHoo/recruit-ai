@extends('layouts.public')
@section('title', 'Video CV miễn phí')

@php
    $maxAnswerMinutes = intdiv($config['max_answer_seconds'], 60);
    $defaultCount = count($config['default_questions']);
    $features = [
        ['Kiểm tra thiết bị', 'Xem trước hình, đo âm lượng micro, chọn camera và quay thử 5 giây trước khi bắt đầu thật.', 'device'],
        ['Quay câu trả lời', "Mỗi câu hỏi một video riêng, có gợi ý ý chính, đếm ngược và đồng hồ {$maxAnswerMinutes} phút.", 'record'],
        ['Thông tin Video CV', 'Ảnh đại diện, họ tên, vị trí mong muốn, giới thiệu ngắn và CV đính kèm.', 'info'],
        ['Hoàn thành & chia sẻ', 'Xem trước đúng trang nhà tuyển dụng sẽ thấy, rồi tạo link chia sẻ chỉ với một nút bấm.', 'share'],
    ];
    $steps = [
        ['Kiểm tra camera và micro', 'Bật camera, chọn thiết bị, nói thử để xem thanh âm lượng và quay thử vài giây.'],
        ['Quay từng câu trả lời', "Bắt đầu với {$defaultCount} câu hỏi gợi ý, sửa hoặc thêm tới {$config['max_questions']} câu. Mỗi câu tối đa {$maxAnswerMinutes} phút, quay lại thoải mái."],
        ['Thêm thông tin và CV', 'Ảnh, họ tên, vị trí mong muốn, vài dòng giới thiệu và một CV để nhà tuyển dụng xem kỹ hơn.'],
        ['Xem trước và chia sẻ', 'Ứng tuyển trên hệ thống để nhà tuyển dụng thấy video ngay trong hồ sơ, hoặc gửi link cho bất kỳ ai.'],
    ];
    $employerSees = [
        ['Hồ sơ ngắn gọn', 'Ảnh, họ tên, vị trí mong muốn, giới thiệu và email liên hệ nếu bạn muốn công khai.'],
        ['CV đính kèm', 'Một nút mở CV ngay trên trang, không cần tải tệp qua email.'],
        ['Video theo từng câu hỏi', 'Mỗi câu trả lời là một video riêng kèm câu hỏi, xem câu nào trước cũng được.'],
        ['Bạn kiểm soát việc chia sẻ', 'Đổi link hoặc tắt chia sẻ bất cứ lúc nào, link cũ ngừng hoạt động ngay.'],
    ];
    $values = [
        ['Khả năng giao tiếp', 'Giọng nói, cách diễn đạt và sắp xếp ý: những điều CV giấy không thể hiện được.', 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z'],
        ['Sự tự tin', 'Nhà tuyển dụng cảm nhận được thái độ và phong thái của bạn trước cả buổi phỏng vấn.', 'M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z'],
        ['Đúng trọng tâm công việc', 'Bạn chọn câu hỏi và nói thẳng vào điều vị trí đó cần, thay vì để nhà tuyển dụng tự đoán.', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    ];
    $tips = [
        ['Chuẩn bị không gian', 'Phòng yên tĩnh, ánh sáng chiếu từ phía trước, phông nền gọn gàng.'],
        ['Kiểm tra thiết bị và mạng', 'Dùng bước kiểm tra thiết bị, sạc đầy pin và giữ kết nối ổn định khi tải lên.'],
        ['Luyện tập vài lần', 'Đọc gợi ý, nói thử thành tiếng, quay thử rồi xem lại trước khi quay thật.'],
        ['Giữ khung hình ổn định', 'Đặt máy trên giá hoặc mặt bàn, camera ngang tầm mắt, không cầm tay.'],
        ['Tự nhiên là tốt nhất', 'Nói như đang trò chuyện, cười nhẹ, nhìn vào ống kính thay vì màn hình.'],
    ];
    $faqs = [
        ['Tạo Video CV có mất phí không?', 'Không. Video CV miễn phí. Bạn cần một tài khoản ứng viên (cũng miễn phí) để lưu video và tạo link chia sẻ.'],
        ['Tôi cần thiết bị gì?', 'Máy tính hoặc điện thoại có camera, micro và trình duyệt mới (Chrome, Edge, Firefox, Safari). Bạn cũng có thể tải lên video đã quay sẵn.'],
        ['Có bao nhiêu câu hỏi và mỗi câu dài bao lâu?', "Mặc định {$defaultCount} câu có gợi ý trả lời. Bạn sửa được nội dung và thêm tới {$config['max_questions']} câu. Mỗi câu trả lời dài tối đa {$maxAnswerMinutes} phút."],
        ['Tôi có thể quay lại không?', 'Có, không giới hạn số lần. Bản cũ được giữ cho tới khi bản mới tải lên thành công. Bản quay thử ở bước kiểm tra thiết bị không bao giờ được tải lên.'],
        ['Ai xem được Video CV của tôi?', 'Video CV riêng tư cho tới khi bạn tạo link. Ai có link đều xem được, và bạn có thể đổi link hoặc tắt chia sẻ bất cứ lúc nào. Nhà tuyển dụng của tin bạn đã ứng tuyển cũng xem được video trong hồ sơ.'],
        ['Video CV có xuất hiện trên Google không?', 'Không. Trang Video CV không được liệt kê ở đâu và yêu cầu công cụ tìm kiếm không lập chỉ mục.'],
        ['CV đính kèm dùng định dạng nào?', 'Chọn một CV bạn đã tạo trên hệ thống, hoặc tải lên tệp PDF, DOC, DOCX. CV tải lên cũng được lưu vào mục Quản lý CV để dùng khi ứng tuyển.'],
    ];
@endphp

@section('content')
    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-900 via-blue-700 to-indigo-700 text-white">
        <div class="pointer-events-none absolute -top-40 -right-40 size-[28rem] rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 -left-20 size-96 rounded-full bg-indigo-400/20 blur-3xl"></div>
        <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold tracking-wider text-blue-200 uppercase">Video CV</p>
                <h1 class="mt-3 text-4xl leading-tight font-extrabold tracking-tight sm:text-5xl">Tạo Video CV miễn phí, gửi nhà tuyển dụng bằng một link</h1>
                <p class="mt-5 max-w-lg text-lg text-blue-100">Quay câu trả lời phỏng vấn ngay trên trình duyệt, kèm ảnh và CV, rồi gửi một link riêng tư. Nhà tuyển dụng nghe bạn nói trước cả buổi phỏng vấn.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    @if ($startUrl)
                        <a href="{{ $startUrl }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-8 py-3 font-semibold text-blue-700 shadow-lg hover:bg-blue-50">{{ $startLabel }} &rarr;</a>
                    @endif
                    <a href="#video-cv-public-title" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/40 px-8 py-3 font-semibold text-white hover:bg-white/10">Xem nhà tuyển dụng thấy gì</a>
                </div>
                <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-blue-100">
                    @foreach (['Miễn phí', 'Khoảng 10 phút', 'Không cần cài đặt'] as $badge)
                        <li class="flex items-center gap-2"><svg class="size-4 text-emerald-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>{{ $badge }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Recorder mock --}}
            <div class="relative mx-auto w-full max-w-md" aria-hidden="true">
                <div class="rounded-2xl bg-white p-4 text-slate-800 shadow-2xl">
                    <p class="text-[11px] font-semibold tracking-wider text-blue-700 uppercase">Câu 1 / {{ $defaultCount }}</p>
                    <p class="text-sm font-semibold">{{ $config['default_questions'][0]['question'] }}</p>
                    <div class="relative mt-3 aspect-video overflow-hidden rounded-xl bg-gradient-to-br from-slate-700 to-slate-900">
                        <div class="absolute inset-x-0 bottom-0 mx-auto h-3/4 w-1/2 rounded-t-full bg-slate-500/60"></div>
                        <div class="absolute bottom-[55%] left-1/2 size-14 -translate-x-1/2 rounded-full bg-slate-400/70"></div>
                        <span class="absolute top-2 left-2 flex items-center gap-1.5 rounded-full bg-slate-900/70 px-2.5 py-0.5 text-[11px] font-semibold text-white"><span class="size-2 animate-pulse rounded-full bg-red-500"></span>0:48 / {{ $maxAnswerMinutes }}:00</span>
                        <div class="absolute inset-x-0 bottom-0 h-1 bg-white/10"><div class="h-full w-2/5 bg-red-500"></div></div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <span class="rounded-full bg-slate-800 px-4 py-1.5 text-xs font-semibold text-white">■ Dừng quay</span>
                        <span class="ml-auto rounded-full bg-slate-100 px-3 py-1.5 text-xs text-slate-500">Gợi ý: họ tên, chuyên môn…</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FEATURES ============ --}}
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-slate-900">Mọi thứ cần để có một Video CV chỉn chu</h2>
            <p class="mt-3 text-slate-600">Từ kiểm tra thiết bị tới link chia sẻ, tất cả nằm trong trình duyệt.</p>
        </div>
        <div class="mt-12 grid gap-6 md:grid-cols-2">
            @foreach ($features as [$title, $text, $mock])
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="flex h-48 items-center justify-center bg-gradient-to-br from-slate-50 to-blue-50 p-6" aria-hidden="true">
                        @switch($mock)
                            @case('device')
                                <div class="w-full max-w-xs space-y-3 rounded-xl bg-white p-4 shadow">
                                    <div class="flex items-center gap-2 text-xs"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-700">✓</span> Camera · 1280×720</div>
                                    <div class="flex items-center gap-2 text-xs"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-700">✓</span> Micro · Nghe rõ</div>
                                    <div class="flex h-2.5 gap-0.5">@for ($i = 0; $i < 20; $i++)<span class="flex-1 rounded-sm {{ $i < 9 ? 'bg-emerald-500' : ($i < 12 ? 'bg-amber-400' : 'bg-slate-200') }}"></span>@endfor</div>
                                </div>
                                @break
                            @case('record')
                                <div class="w-full max-w-xs space-y-2">
                                    @foreach (array_slice($config['default_questions'], 0, 3) as $i => $q)
                                        <div class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs shadow-sm">
                                            <span class="grid size-5 shrink-0 place-items-center rounded-full {{ $i < 2 ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-50 text-blue-700' }} font-bold">{{ $i < 2 ? '✓' : $i + 1 }}</span>
                                            <span class="truncate">{{ $q['question'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @break
                            @case('info')
                                <div class="flex w-full max-w-xs items-center gap-3 rounded-xl bg-white p-4 shadow">
                                    <span class="grid size-12 shrink-0 place-items-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 font-bold text-white">NA</span>
                                    <div class="min-w-0 space-y-1.5"><p class="text-sm font-semibold">Nguyễn An</p><p class="text-xs text-blue-700">Chuyên viên Marketing</p><p class="rounded bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">CV_NguyenAn.pdf</p></div>
                                </div>
                                @break
                            @case('share')
                                <div class="w-full max-w-xs rounded-xl bg-white p-4 shadow">
                                    <p class="text-xs font-semibold text-slate-700">Link chia sẻ</p>
                                    <div class="mt-2 flex gap-2"><span class="flex-1 truncate rounded-lg bg-slate-100 px-2 py-1.5 font-mono text-[11px] text-slate-500">{{ parse_url(config('app.url'), PHP_URL_HOST) }}/v/Xk29…</span><span class="rounded-lg bg-blue-600 px-2.5 py-1.5 text-[11px] font-semibold text-white">Sao chép</span></div>
                                    <p class="mt-2 text-[11px] text-emerald-700">● Đang chia sẻ · 12 lượt xem</p>
                                </div>
                                @break
                        @endswitch
                    </div>
                    <div class="p-6">
                        <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $text }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ============ HOW IT WORKS ============ --}}
    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-slate-900">Cách hoạt động</h2>
                <p class="mt-2 text-slate-600">Bốn bước, khoảng 10 phút</p>
            </div>
            <ol class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($steps as $i => [$title, $text])
                    <li class="relative rounded-2xl border border-slate-200 bg-white p-6">
                        <span class="grid size-10 place-items-center rounded-full bg-blue-600 font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ============ WHAT EMPLOYERS SEE ============ --}}
    <section class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6" id="video-cv-public-title">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div aria-hidden="true" class="rounded-2xl bg-slate-100 p-4 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-5">
                    <div class="rounded-xl bg-white p-4 text-center shadow-sm sm:col-span-2">
                        <span class="mx-auto grid size-16 place-items-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-lg font-bold text-white">NA</span>
                        <p class="mt-3 text-sm font-bold">Nguyễn An</p>
                        <p class="text-xs text-blue-700">Chuyên viên Marketing</p>
                        <p class="mt-2 text-[11px] text-slate-500">an.nguyen@email.com</p>
                        <span class="mt-3 block rounded-full bg-blue-600 py-1.5 text-xs font-semibold text-white">Xem CV</span>
                    </div>
                    <div class="space-y-3 sm:col-span-3">
                        @foreach ([['Giới thiệu bản thân', '1:05'], ['Dự án tự hào nhất', '1:32'], ['Vì sao bạn phù hợp', '0:58']] as $i => [$q, $d])
                            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                                <div class="flex items-center gap-2 px-3 py-2 text-xs"><span class="grid size-5 place-items-center rounded-full bg-blue-50 font-bold text-blue-700">{{ $i + 1 }}</span><span class="flex-1 truncate font-semibold">{{ $q }}</span><span class="text-slate-400">{{ $d }}</span></div>
                                <div class="flex h-14 items-center justify-center bg-slate-800"><span class="grid size-7 place-items-center rounded-full bg-white/90 text-blue-700"><svg class="ml-0.5 size-3" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-slate-900">Nhà tuyển dụng thấy gì khi mở link</h2>
                <p class="mt-3 text-slate-600">Một trang gọn gàng, mở được trên mọi thiết bị, không cần đăng nhập.</p>
                <ul class="mt-8 space-y-5">
                    @foreach ($employerSees as [$title, $text])
                        <li class="flex gap-4">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700"><svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg></span>
                            <div><h3 class="font-semibold text-slate-900">{{ $title }}</h3><p class="mt-0.5 text-sm text-slate-600">{{ $text }}</p></div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ============ VALUE ============ --}}
    <section class="bg-slate-950 py-20 text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h2 class="text-center text-3xl font-bold">Đừng chỉ kể, hãy cho nhà tuyển dụng thấy</h2>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ($values as [$title, $text, $icon])
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                        <svg class="size-9 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        <h3 class="mt-4 font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ TIPS ============ --}}
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <h2 class="mx-auto max-w-2xl text-center text-3xl font-bold text-slate-900">Tự tin trước camera, chuyên nghiệp trong từng khung hình</h2>
        <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($tips as $i => [$title, $text])
                <div class="rounded-2xl border border-slate-200 p-5">
                    <span class="text-2xl font-extrabold text-blue-200">0{{ $i + 1 }}</span>
                    <h3 class="mt-2 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-center text-3xl font-bold text-slate-900">Câu hỏi thường gặp về Video CV</h2>
            <div class="mt-10 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                @foreach ($faqs as [$q, $a])
                    <details class="group p-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                            {{ $q }}
                            <svg class="size-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <div class="rounded-3xl bg-gradient-to-br from-blue-700 to-indigo-700 px-6 py-14 text-center text-white sm:px-14">
            <h2 class="text-3xl font-bold">Sẵn sàng để nhà tuyển dụng nhớ đến bạn?</h2>
            @if ($startUrl)
                <a href="{{ $startUrl }}" class="mt-8 inline-flex items-center gap-2 rounded-full bg-white px-8 py-3 font-semibold text-blue-700 shadow-lg hover:bg-blue-50">{{ $startLabel }} &rarr;</a>
            @endif
            <div class="mx-auto mt-10 grid max-w-2xl gap-4 text-left sm:grid-cols-2">
                <div class="rounded-2xl bg-white/10 p-5">
                    <p class="font-semibold">Miễn phí, không cài đặt</p>
                    <p class="mt-1 text-sm text-blue-100">Quay ngay trên trình duyệt máy tính hoặc điện thoại.</p>
                </div>
                <div class="rounded-2xl bg-white/10 p-5">
                    <p class="font-semibold">Riêng tư cho tới khi bạn chia sẻ</p>
                    <p class="mt-1 text-sm text-blue-100">Chỉ người có link mới xem được. Tắt chia sẻ bất cứ lúc nào.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
