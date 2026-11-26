@extends('layouts.app')
@section('title', 'Video CV')

@php
    $steps = [
        'device' => 'Kiểm tra thiết bị',
        'record' => 'Quay câu trả lời',
        'info' => 'Thông tin & CV',
        'share' => 'Xem trước & chia sẻ',
    ];
    $stepKeys = array_keys($steps);
    $answers = $videoCv->answers;
    $recordedCount = $videoCv->recordedAnswers()->count();
    $maxAnswerMinutes = intdiv($config['max_answer_seconds'], 60);
    $maxMb = intdiv($config['max_upload_kb'], 1024);
    $missing = $videoCv->missingForShare();
    $stepDone = [
        'device' => $recordedCount > 0,
        'record' => $recordedCount > 0,
        'info' => filled($videoCv->full_name) && filled($videoCv->position),
        'share' => $videoCv->isShared(),
    ];
@endphp

@section('content')
    {{-- Status + stepper --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        @if ($videoCv->isShared())
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"><span class="size-1.5 rounded-full bg-emerald-500"></span> Đang chia sẻ · {{ number_format($videoCv->view_count) }} lượt xem</span>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"><span class="size-1.5 rounded-full bg-slate-400"></span> Riêng tư</span>
        @endif
        <span class="text-sm text-slate-500">{{ $recordedCount }}/{{ $answers->count() }} câu đã quay · tổng {{ $videoCv->totalDurationLabel() }}</span>
    </div>

    <nav class="mb-8 grid grid-cols-2 gap-2 sm:grid-cols-4" aria-label="Các bước tạo Video CV">
        @foreach ($steps as $key => $label)
            @php $active = $key === $step; @endphp
            <a href="{{ route('candidate.video-cv.edit', ['step' => $key]) }}" @class([
                'flex items-center gap-3 rounded-xl border px-3 py-3 text-sm transition',
                'border-blue-600 bg-blue-50 text-blue-800' => $active,
                'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => ! $active,
            ]) @if ($active) aria-current="step" @endif>
                <span @class([
                    'grid size-7 shrink-0 place-items-center rounded-full text-xs font-bold',
                    'bg-blue-600 text-white' => $active,
                    'bg-emerald-100 text-emerald-700' => ! $active && $stepDone[$key],
                    'bg-slate-100 text-slate-500' => ! $active && ! $stepDone[$key],
                ])>{!! ! $active && $stepDone[$key] ? '&#10003;' : $loop->iteration !!}</span>
                <span class="font-semibold leading-tight">{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= STEP 1: DEVICE CHECK ================= --}}
    @if ($step === 'device')
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 lg:col-span-2">
                <div class="relative aspect-video overflow-hidden rounded-xl bg-slate-900">
                    <video id="device-live" class="hidden size-full -scale-x-100 object-cover" autoplay muted playsinline></video>
                    <video id="device-test" class="hidden size-full object-contain" controls playsinline></video>
                    <div id="device-off" class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center text-slate-300">
                        <svg class="size-12 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        <p class="max-w-sm text-sm">Bật camera và micro để kiểm tra hình ảnh, âm thanh trước khi quay. Trình duyệt sẽ hỏi quyền truy cập.</p>
                        <button type="button" id="device-start" class="btn-primary rounded-full">Bật camera &amp; micro</button>
                    </div>
                    <div id="device-test-badge" class="absolute top-3 left-3 hidden items-center gap-2 rounded-full bg-slate-900/70 px-3 py-1 text-sm font-semibold text-white"><span class="size-2.5 animate-pulse rounded-full bg-red-500"></span> Đang quay thử <span id="device-test-time">5</span>s</div>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="form-label">Camera</span>
                        <select id="camera-select" class="form-input" disabled><option>Chưa bật camera</option></select>
                    </label>
                    <label class="block">
                        <span class="form-label">Micro</span>
                        <select id="mic-select" class="form-input" disabled><option>Chưa bật micro</option></select>
                    </label>
                </div>

                <div class="mt-4">
                    <p class="form-label">Âm lượng micro <span class="font-normal text-slate-500">(hãy nói thử vài câu)</span></p>
                    <div class="flex h-3 gap-1">
                        @for ($i = 0; $i < 24; $i++) <span data-meter class="flex-1 rounded-sm bg-slate-200"></span> @endfor
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                    <button type="button" id="device-test-btn" class="btn-secondary rounded-full" disabled>Quay thử 5 giây</button>
                    <p class="text-xs text-slate-500">Bản quay thử chỉ phát lại trên máy bạn, không được tải lên.</p>
                </div>
                <p id="device-error" class="mt-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></p>
            </section>

            <aside class="space-y-6 self-start">
                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold text-slate-900">Kết quả kiểm tra</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach (['camera' => 'Camera', 'mic' => 'Micro', 'secure' => 'Kết nối an toàn'] as $key => $label)
                            <li class="flex items-center gap-3">
                                <span data-check="{{ $key }}" class="grid size-6 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-400">?</span>
                                <span class="flex-1 text-slate-700">{{ $label }}</span>
                                <span data-check-text="{{ $key }}" class="text-xs text-slate-500">Chưa kiểm tra</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold text-slate-900">Trước khi quay</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <li>• Chọn nơi yên tĩnh, đủ sáng, phông nền gọn gàng.</li>
                        <li>• Mặt hướng về nguồn sáng, camera ngang tầm mắt.</li>
                        <li>• Dùng tai nghe có micro nếu phòng có tiếng vang.</li>
                        <li>• Đóng các ứng dụng khác đang dùng camera.</li>
                    </ul>
                </section>
                <a href="{{ route('candidate.video-cv.edit', ['step' => 'record']) }}" class="btn-primary w-full justify-center rounded-full">Tiếp tục: Quay câu trả lời &rarr;</a>
            </aside>
        </div>
    @endif

    {{-- ================= STEP 2: RECORD ANSWERS ================= --}}
    @if ($step === 'record')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-slate-600">Trả lời từng câu hỏi bằng một video ngắn, tối đa <b>{{ $maxAnswerMinutes }} phút</b> mỗi câu. Câu nào chưa ưng thì quay lại, bản cũ được giữ cho tới khi bản mới lưu xong.</p>
                    <button type="button" id="toggle-questions" class="text-sm font-semibold text-blue-700 hover:underline">Sửa câu hỏi</button>
                </div>

                {{-- Question editor --}}
                <form id="questions-form" method="POST" action="{{ route('candidate.video-cv.questions') }}" class="hidden rounded-2xl border border-blue-200 bg-blue-50/50 p-5">
                    @csrf
                    @method('PUT')
                    <p class="font-semibold text-slate-900">Danh sách câu hỏi <span class="font-normal text-slate-500">(tối đa {{ $config['max_questions'] }})</span></p>
                    <p class="mt-1 text-xs text-slate-500">Xóa một câu hỏi sẽ xóa luôn video trả lời của câu đó khi bạn bấm Lưu.</p>
                    <div id="question-rows" class="mt-4 space-y-2">
                        @foreach (old('questions', $answers->map(fn ($a) => ['id' => $a->id, 'question' => $a->question])->all()) as $row)
                            <div data-question-row class="flex gap-2">
                                <input type="hidden" data-field="id" value="{{ $row['id'] ?? '' }}">
                                <input type="text" data-field="question" value="{{ $row['question'] }}" maxlength="300" required class="form-input flex-1" placeholder="Nhập câu hỏi">
                                <button type="button" data-remove-question class="rounded-lg px-3 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Xóa câu hỏi">&times;</button>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="button" id="add-question" class="btn-secondary rounded-full">+ Thêm câu hỏi</button>
                        <span class="flex-1"></span>
                        <button type="button" id="cancel-questions" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Hủy</button>
                        <button class="btn-primary rounded-full">Lưu câu hỏi</button>
                    </div>
                </form>

                {{-- Answers --}}
                @foreach ($answers as $answer)
                    <section class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex items-start gap-3">
                            <span @class([
                                'grid size-8 shrink-0 place-items-center rounded-full text-sm font-bold',
                                'bg-emerald-100 text-emerald-700' => $answer->hasVideo(),
                                'bg-blue-50 text-blue-700' => ! $answer->hasVideo(),
                            ])>{!! $answer->hasVideo() ? '&#10003;' : $answer->position !!}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Câu {{ $answer->position }}</p>
                                <h3 class="font-semibold text-slate-900">{{ $answer->question }}</h3>
                                @if ($hint = $answer->hint())
                                    <p class="mt-1 text-sm text-slate-500">Gợi ý: {{ $hint }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($answer->hasVideo())
                            <video controls preload="metadata" playsinline class="mt-4 aspect-video w-full rounded-xl bg-slate-900" src="{{ route('candidate.video-cv.answers.file', $answer) }}?v={{ $answer->recorded_at?->timestamp }}"></video>
                            <p class="mt-2 text-xs text-slate-500">{{ $answer->durationLabel() }} · {{ $answer->source === 'record' ? 'Quay trên trình duyệt' : 'Tải lên từ máy' }} · {{ $answer->recorded_at?->format('d/m/Y H:i') }}</p>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <button type="button" data-record="{{ $answer->id }}" data-position="{{ $answer->position }}" data-question="{{ $answer->question }}" data-hint="{{ $answer->hint() }}" data-upload-url="{{ route('candidate.video-cv.answers.store', $answer) }}" @class(['rounded-full', 'btn-primary' => ! $answer->hasVideo(), 'btn-secondary' => $answer->hasVideo()])>
                                {{ $answer->hasVideo() ? 'Quay lại' : '● Quay câu trả lời' }}
                            </button>
                            <button type="button" data-upload-file="{{ $answer->id }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Tải video từ máy</button>
                            @if ($answer->hasVideo())
                                <form method="POST" action="{{ route('candidate.video-cv.answers.destroy', $answer) }}" class="ml-auto" onsubmit="return confirm('Xóa video trả lời câu {{ $answer->position }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-full px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Xóa video</button>
                                </form>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="space-y-6 self-start lg:sticky lg:top-24">
                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold text-slate-900">Tiến độ</h3>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $recordedCount }}<span class="text-base font-medium text-slate-400">/{{ $answers->count() }} câu</span></p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $answers->count() ? round($recordedCount / $answers->count() * 100) : 0 }}%"></div></div>
                    <p class="mt-3 text-xs text-slate-500">Cần ít nhất 1 câu để chia sẻ. Trả lời đủ các câu giúp nhà tuyển dụng hiểu bạn rõ hơn.</p>
                </section>
                <section class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
                    <h3 class="font-semibold text-slate-900">Mẹo trả lời</h3>
                    <ul class="mt-3 space-y-2">
                        <li>• Nhìn vào ống kính, không nhìn màn hình.</li>
                        <li>• Nói chậm, rõ, cười nhẹ khi mở đầu.</li>
                        <li>• Đọc gợi ý trước, nói tự nhiên thay vì đọc thuộc lòng.</li>
                        <li>• 60–90 giây mỗi câu là vừa đủ.</li>
                    </ul>
                </section>
                <a href="{{ route('candidate.video-cv.edit', ['step' => 'info']) }}" class="btn-primary w-full justify-center rounded-full">Tiếp tục: Thông tin &amp; CV &rarr;</a>
            </aside>
        </div>

        <input id="answer-file-input" type="file" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov" class="hidden">

        {{-- Recorder modal --}}
        <div id="recorder" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4" role="dialog" aria-modal="true" aria-labelledby="rec-question">
            <div class="max-h-full w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start gap-3 border-b border-slate-100 p-5">
                    <div class="min-w-0 flex-1">
                        <p id="rec-label" class="text-xs font-semibold tracking-wider text-blue-700 uppercase"></p>
                        <h3 id="rec-question" class="font-semibold text-slate-900"></h3>
                        <p id="rec-hint" class="mt-1 text-sm text-slate-500"></p>
                    </div>
                    <button type="button" id="rec-close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-5">
                    <div class="relative aspect-video overflow-hidden rounded-xl bg-slate-900">
                        <video id="rec-live" class="hidden size-full -scale-x-100 object-cover" autoplay muted playsinline></video>
                        <video id="rec-review" class="hidden size-full object-contain" controls playsinline></video>
                        <div id="rec-loading" class="absolute inset-0 grid place-items-center text-sm text-slate-400">Đang bật camera…</div>
                        <div id="rec-countdown" class="absolute inset-0 hidden place-items-center bg-slate-900/40 text-7xl font-extrabold text-white"></div>
                        <div id="rec-badge" class="absolute top-3 left-3 hidden items-center gap-2 rounded-full bg-slate-900/70 px-3 py-1 text-sm font-semibold text-white">
                            <span class="size-2.5 animate-pulse rounded-full bg-red-500"></span>
                            <span id="rec-time">0:00</span><span class="text-slate-400">/ {{ $maxAnswerMinutes }}:00</span>
                        </div>
                        <div class="absolute inset-x-0 bottom-0 h-1 bg-white/10"><div id="rec-progress" class="h-full w-0 bg-red-500 transition-[width] duration-1000 ease-linear"></div></div>
                    </div>

                    <div id="rec-upload-progress" class="mt-4 hidden">
                        <div class="flex justify-between text-xs text-slate-600"><span>Đang tải video lên…</span><span id="rec-progress-text">0%</span></div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100"><div id="rec-progress-bar" class="h-full w-0 rounded-full bg-blue-600 transition-[width]"></div></div>
                    </div>
                    <p id="rec-error" class="mt-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></p>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button" id="rec-start" class="btn-primary hidden rounded-full bg-red-600 hover:bg-red-700">● Bắt đầu quay</button>
                        <button type="button" id="rec-stop" class="btn-primary hidden rounded-full bg-slate-800 hover:bg-slate-900">■ Dừng quay</button>
                        <button type="button" id="rec-retake" class="btn-secondary hidden rounded-full">Quay lại</button>
                        <span id="rec-meta" class="flex-1 text-xs text-slate-500">Tối đa {{ $maxAnswerMinutes }} phút, tự dừng khi hết giờ.</span>
                        <button type="button" id="rec-save" class="btn-primary hidden rounded-full">Lưu câu trả lời</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= STEP 3: PROFILE & CV ================= --}}
    @if ($step === 'info')
        <form method="POST" action="{{ route('candidate.video-cv.info') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
            @csrf
            @method('PUT')
            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2">
                <div class="flex flex-wrap items-center gap-5">
                    <div class="relative">
                        <img id="photo-preview" src="{{ $videoCv->photo_path ? route('candidate.video-cv.photo').'?v='.$videoCv->updated_at->timestamp : '' }}" alt="" @class(['size-24 rounded-full object-cover ring-4 ring-blue-50', 'hidden' => ! $videoCv->photo_path])>
                        <span id="photo-placeholder" @class(['grid size-24 place-items-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-2xl font-bold text-white ring-4 ring-blue-50', 'hidden' => $videoCv->photo_path])>{{ $videoCv->initials() }}</span>
                    </div>
                    <div class="space-y-2">
                        <label class="btn-secondary inline-flex cursor-pointer rounded-full">
                            Chọn ảnh đại diện
                            <input id="photo-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                        </label>
                        @if ($videoCv->photo_path)
                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300"> Xóa ảnh hiện tại</label>
                        @endif
                        <p class="text-xs text-slate-500">JPG, PNG hoặc WEBP, tối đa {{ intdiv($config['max_photo_kb'], 1024) }} MB. Nên dùng ảnh chân dung rõ mặt.</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="form-label">Họ tên <span class="text-red-500">*</span></span>
                        <input name="full_name" value="{{ old('full_name', $videoCv->full_name) }}" required maxlength="100" class="form-input">
                    </label>
                    <label class="block">
                        <span class="form-label">Vị trí mong muốn <span class="text-red-500">*</span></span>
                        <input name="position" value="{{ old('position', $videoCv->position) }}" required maxlength="150" class="form-input" placeholder="VD: Lập trình viên PHP">
                    </label>
                </div>
                <label class="block">
                    <span class="form-label">Giới thiệu ngắn</span>
                    <textarea id="intro" name="intro" rows="4" maxlength="600" class="form-input" placeholder="2–3 câu về kinh nghiệm, thế mạnh và mục tiêu nghề nghiệp.">{{ old('intro', $videoCv->intro) }}</textarea>
                    <span class="mt-1 block text-right text-xs text-slate-400"><span id="intro-count">0</span>/600</span>
                </label>
                <label class="block">
                    <span class="form-label">Email công khai <span class="font-normal text-slate-500">(không bắt buộc)</span></span>
                    <input type="email" name="public_email" value="{{ old('public_email', $videoCv->public_email) }}" class="form-input" placeholder="Để nhà tuyển dụng liên hệ trực tiếp">
                </label>
            </section>

            <aside class="space-y-6 self-start">
                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold text-slate-900">CV đính kèm</h3>
                    <p class="mt-1 text-xs text-slate-500">Nhà tuyển dụng mở được CV này ngay trên trang Video CV.</p>
                    <div class="mt-4 space-y-2">
                        @php $selectedCv = old('cv_id', $videoCv->cv_id); @endphp
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="cv_id" value="" @checked(! $selectedCv) class="text-blue-600"> Không đính kèm
                        </label>
                        @foreach ($cvs as $cv)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                <input type="radio" name="cv_id" value="{{ $cv->id }}" @checked((int) $selectedCv === $cv->id) class="text-blue-600">
                                <span class="min-w-0 flex-1 truncate">{{ $cv->title }}</span>
                                @if ($cv->is_default) <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">Mặc định</span> @endif
                            </label>
                        @endforeach
                    </div>
                    <label class="mt-4 block border-t border-slate-100 pt-4">
                        <span class="form-label">Hoặc tải CV mới</span>
                        <input type="file" name="cv_file" accept=".pdf,.doc,.docx" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold hover:file:bg-slate-200">
                        <span class="mt-1 block text-xs text-slate-500">PDF, DOC, DOCX tối đa {{ intdiv(config('recruitment.cv.max_upload_kb'), 1024) }} MB. CV được lưu vào mục Quản lý CV.</span>
                    </label>
                </section>
                <button class="btn-primary w-full justify-center rounded-full">Lưu và tiếp tục &rarr;</button>
            </aside>
        </form>
    @endif

    {{-- ================= STEP 4: PREVIEW & SHARE ================= --}}
    @if ($step === 'share')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <div class="flex flex-wrap items-center gap-4">
                        @if ($videoCv->photo_path)
                            <img src="{{ route('candidate.video-cv.photo') }}?v={{ $videoCv->updated_at->timestamp }}" alt="" class="size-16 rounded-full object-cover">
                        @else
                            <span class="grid size-16 place-items-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-xl font-bold text-white">{{ $videoCv->initials() }}</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-bold text-slate-900">{{ $videoCv->full_name ?: 'Chưa nhập họ tên' }}</p>
                            <p class="text-sm text-blue-700">{{ $videoCv->position ?: 'Chưa nhập vị trí mong muốn' }}</p>
                            <p class="text-xs text-slate-500">{{ $recordedCount }} câu trả lời · {{ $videoCv->totalDurationLabel() }} · {{ $videoCv->cv ? 'CV: '.$videoCv->cv->title : 'Không đính kèm CV' }}</p>
                        </div>
                        <a href="{{ route('candidate.video-cv.preview') }}" target="_blank" class="btn-secondary rounded-full">Xem trước trang công khai</a>
                    </div>

                    @if ($missing)
                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            <p class="font-semibold">Cần hoàn thành trước khi chia sẻ:</p>
                            <ul class="mt-1 list-inside list-disc">
                                @foreach ($missing as $item) <li>{{ $item }}</li> @endforeach
                            </ul>
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold text-slate-900">Chia sẻ bằng link</h3>
                    <p class="mt-1 text-sm text-slate-600">Gửi link cho nhà tuyển dụng qua email, Zalo hoặc LinkedIn. Ai có link đều xem được, bạn có thể đổi link hoặc tắt chia sẻ bất cứ lúc nào.</p>

                    @if ($videoCv->isShared())
                        <div class="mt-4 flex gap-2">
                            <input id="share-url" readonly value="{{ $videoCv->shareUrl() }}" class="form-input flex-1 bg-slate-50 font-mono text-sm" onclick="this.select()">
                            <button type="button" id="copy-share" class="btn-primary shrink-0 rounded-lg">Sao chép</button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Tạo lúc {{ $videoCv->shared_at?->format('d/m/Y H:i') }} · {{ number_format($videoCv->view_count) }} lượt xem</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ $videoCv->shareUrl() }}" target="_blank" class="btn-secondary rounded-full">Mở link</a>
                            <form method="POST" action="{{ route('candidate.video-cv.share') }}" onsubmit="return confirm('Đổi link? Link cũ sẽ không xem được nữa.')">
                                @csrf
                                <button class="btn-secondary rounded-full">Đổi link mới</button>
                            </form>
                            <form method="POST" action="{{ route('candidate.video-cv.share.destroy') }}" onsubmit="return confirm('Tắt chia sẻ? Link hiện tại sẽ không xem được nữa.')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-full px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Tắt chia sẻ</button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('candidate.video-cv.share') }}" class="mt-4">
                            @csrf
                            <button class="btn-primary rounded-full" @disabled($missing)>Tạo link chia sẻ</button>
                        </form>
                    @endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold text-slate-900">Ứng tuyển qua {{ config('app.name') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">Không cần link: khi bạn ứng tuyển, nhà tuyển dụng xem được Video CV ngay trong hồ sơ của bạn, kể cả khi bạn chưa bật chia sẻ.</p>
                    <p class="mt-3 text-sm text-slate-500">Bạn đã ứng tuyển {{ $applicationCount }} tin. <a href="{{ route('jobs.index') }}" class="font-semibold text-blue-700 hover:underline">Tìm việc làm &rarr;</a></p>
                </section>
            </div>

            <aside class="space-y-6 self-start">
                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold text-slate-900">Quyền riêng tư</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <li>• Video CV riêng tư cho tới khi bạn tạo link.</li>
                        <li>• Link không xuất hiện trên công cụ tìm kiếm.</li>
                        <li>• Đổi link hoặc tắt chia sẻ thì link cũ ngừng hoạt động ngay.</li>
                    </ul>
                </section>
                <section class="rounded-2xl border border-red-200 bg-white p-6">
                    <h3 class="font-semibold text-red-700">Xóa Video CV</h3>
                    <p class="mt-1 text-sm text-slate-600">Xóa toàn bộ video, ảnh và link chia sẻ. Không thể hoàn tác.</p>
                    <form method="POST" action="{{ route('candidate.video-cv.destroy') }}" class="mt-4" onsubmit="return confirm('Xóa toàn bộ Video CV? Thao tác không thể hoàn tác.')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Xóa Video CV</button>
                    </form>
                </section>
            </aside>
        </div>
    @endif

    <script>
        (() => {
            const $ = (id) => document.getElementById(id);
            const show = (el, on = true) => el && el.classList.toggle('hidden', !on);
            const showFlex = (el, on) => { el.classList.toggle('hidden', !on); el.classList.toggle('flex', on); };
            const fmt = (s) => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`;
            const store = {
                get: (k) => { try { return localStorage.getItem(k); } catch { return null; } },
                set: (k, v) => { try { localStorage.setItem(k, v); } catch {} },
            };
            const MEDIA_ERRORS = {
                NotAllowedError: 'Bạn chưa cho phép dùng camera/micro. Bấm biểu tượng ổ khóa trên thanh địa chỉ để cấp quyền rồi thử lại.',
                NotFoundError: 'Không tìm thấy camera hoặc micro trên máy này.',
                NotReadableError: 'Camera đang được ứng dụng khác sử dụng. Hãy đóng ứng dụng đó rồi thử lại.',
                OverconstrainedError: 'Thiết bị đã chọn không còn khả dụng. Hãy chọn lại camera/micro ở bước Kiểm tra thiết bị.',
                SecurityError: 'Trình duyệt chỉ cho quay video trên kết nối an toàn (https).',
            };
            const mediaError = (e) => MEDIA_ERRORS[e.name] || 'Không bật được camera: ' + e.message;
            const supported = () => !!(navigator.mediaDevices?.getUserMedia && window.MediaRecorder);

            // Camera/mic chosen in the device check are remembered for recording.
            function constraints() {
                const cam = store.get('videoCv.camera'), mic = store.get('videoCv.mic');
                return {
                    video: { width: { ideal: 1280 }, height: { ideal: 720 }, ...(cam ? { deviceId: { exact: cam } } : { facingMode: 'user' }) },
                    audio: { echoCancellation: true, noiseSuppression: true, ...(mic ? { deviceId: { exact: mic } } : {}) },
                };
            }
            async function openStream() {
                try {
                    return await navigator.mediaDevices.getUserMedia(constraints());
                } catch (e) {
                    if (e.name !== 'OverconstrainedError' && e.name !== 'NotFoundError') throw e;
                    store.set('videoCv.camera', ''); store.set('videoCv.mic', '');
                    return navigator.mediaDevices.getUserMedia(constraints());
                }
            }
            const mimeType = ['video/mp4', 'video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm']
                .find((t) => window.MediaRecorder?.isTypeSupported?.(t)) || '';
            const newRecorder = (stream) => new MediaRecorder(stream, { ...(mimeType && { mimeType }), videoBitsPerSecond: 2_000_000 });

            // ================= Step 1: device check =================
            if ($('device-start')) {
                const live = $('device-live'), test = $('device-test'), err = $('device-error');
                const camSel = $('camera-select'), micSel = $('mic-select');
                const meter = [...document.querySelectorAll('[data-meter]')];
                let stream = null, audioCtx = null, raf = null;

                const check = (key, ok, text) => {
                    const icon = document.querySelector(`[data-check="${key}"]`);
                    icon.textContent = ok ? '✓' : '✕';
                    icon.className = `grid size-6 place-items-center rounded-full text-xs font-bold ${ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'}`;
                    document.querySelector(`[data-check-text="${key}"]`).textContent = text;
                };
                check('secure', window.isSecureContext, window.isSecureContext ? 'Đạt' : 'Cần https');

                async function fillDevices() {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const fill = (select, kind, current) => {
                        const list = devices.filter((d) => d.kind === kind);
                        select.innerHTML = list.map((d, i) => `<option value="${d.deviceId}">${d.label || (kind === 'videoinput' ? 'Camera ' : 'Micro ') + (i + 1)}</option>`).join('');
                        if (current) select.value = current;
                        select.disabled = list.length === 0;
                    };
                    fill(camSel, 'videoinput', stream.getVideoTracks()[0]?.getSettings().deviceId);
                    fill(micSel, 'audioinput', stream.getAudioTracks()[0]?.getSettings().deviceId);
                }

                function watchLevel() {
                    cancelAnimationFrame(raf);
                    audioCtx?.close();
                    audioCtx = new AudioContext();
                    const analyser = audioCtx.createAnalyser();
                    analyser.fftSize = 512;
                    audioCtx.createMediaStreamSource(stream).connect(analyser);
                    const data = new Uint8Array(analyser.fftSize);
                    let heard = false;
                    const tick = () => {
                        analyser.getByteTimeDomainData(data);
                        const peak = data.reduce((m, v) => Math.max(m, Math.abs(v - 128)), 0) / 128;
                        const lit = Math.round(Math.min(1, peak * 2.5) * meter.length);
                        meter.forEach((bar, i) => bar.className = `flex-1 rounded-sm ${i < lit ? (i > meter.length * 0.8 ? 'bg-red-500' : i > meter.length * 0.55 ? 'bg-amber-400' : 'bg-emerald-500') : 'bg-slate-200'}`);
                        if (!heard && peak > 0.08) { heard = true; check('mic', true, 'Nghe rõ'); }
                        raf = requestAnimationFrame(tick);
                    };
                    tick();
                }

                async function start() {
                    show(err, false);
                    if (!supported()) { show(err); err.textContent = 'Trình duyệt này không hỗ trợ quay video. Hãy dùng Chrome, Edge, Firefox hoặc Safari bản mới.'; return; }
                    stream?.getTracks().forEach((t) => t.stop());
                    try {
                        stream = await openStream();
                    } catch (e) {
                        show(err); err.textContent = mediaError(e);
                        check(e.name === 'NotAllowedError' ? 'camera' : 'camera', false, 'Không bật được');
                        return;
                    }
                    live.srcObject = stream;
                    show($('device-off'), false); show(test, false); show(live);
                    const settings = stream.getVideoTracks()[0]?.getSettings() || {};
                    check('camera', true, settings.width ? `${settings.width}×${settings.height}` : 'Đạt');
                    check('mic', !!stream.getAudioTracks().length, stream.getAudioTracks().length ? 'Đang nghe…' : 'Không có micro');
                    $('device-test-btn').disabled = false;
                    await fillDevices();
                    watchLevel();
                }

                $('device-start').addEventListener('click', start);
                camSel.addEventListener('change', () => { store.set('videoCv.camera', camSel.value); start(); });
                micSel.addEventListener('change', () => { store.set('videoCv.mic', micSel.value); start(); });

                $('device-test-btn').addEventListener('click', () => {
                    if (!stream) return;
                    const btn = $('device-test-btn');
                    btn.disabled = true;
                    show(test, false); show(live);
                    const chunks = [];
                    const rec = newRecorder(stream);
                    rec.ondataavailable = (e) => e.data.size && chunks.push(e.data);
                    rec.onstop = () => {
                        if (test.src) URL.revokeObjectURL(test.src);
                        test.src = URL.createObjectURL(new Blob(chunks, { type: (rec.mimeType || 'video/webm').split(';')[0] }));
                        show(live, false); show(test);
                        test.play();
                        showFlex($('device-test-badge'), false);
                        btn.disabled = false;
                        btn.textContent = 'Quay thử lại';
                    };
                    let left = 5;
                    $('device-test-time').textContent = left;
                    showFlex($('device-test-badge'), true);
                    rec.start();
                    const t = setInterval(() => {
                        $('device-test-time').textContent = --left;
                        if (left <= 0) { clearInterval(t); rec.stop(); }
                    }, 1000);
                });

                window.addEventListener('beforeunload', () => stream?.getTracks().forEach((t) => t.stop()));
            }

            // ================= Step 2: questions editor =================
            const qForm = $('questions-form');
            if (qForm) {
                const MAX_Q = @js($config['max_questions']);
                const rows = $('question-rows');
                const toggle = (on) => { show(qForm, on); $('toggle-questions').textContent = on ? 'Đóng' : 'Sửa câu hỏi'; };
                const refresh = () => {
                    const list = rows.querySelectorAll('[data-question-row]');
                    $('add-question').disabled = list.length >= MAX_Q;
                    list.forEach((row) => row.querySelector('[data-remove-question]').disabled = list.length <= 1);
                };
                $('toggle-questions').addEventListener('click', () => toggle(qForm.classList.contains('hidden')));
                $('cancel-questions').addEventListener('click', () => window.location.reload());
                rows.addEventListener('click', (e) => {
                    if (!e.target.closest('[data-remove-question]')) return;
                    e.target.closest('[data-question-row]').remove();
                    refresh();
                });
                $('add-question').addEventListener('click', () => {
                    const row = rows.querySelector('[data-question-row]').cloneNode(true);
                    row.querySelector('[data-field="id"]').value = '';
                    row.querySelector('[data-field="question"]').value = '';
                    rows.appendChild(row);
                    row.querySelector('[data-field="question"]').focus();
                    refresh();
                });
                qForm.addEventListener('submit', () => {
                    rows.querySelectorAll('[data-question-row]').forEach((row, i) => {
                        row.querySelector('[data-field="id"]').name = `questions[${i}][id]`;
                        row.querySelector('[data-field="question"]').name = `questions[${i}][question]`;
                    });
                });
                refresh();
                @if ($errors->has('questions') || $errors->has('questions.*'))
                    toggle(true);
                @endif
            }

            // ================= Step 2: recorder modal =================
            const modal = $('recorder');
            if (modal) {
                const MAX_SECONDS = @js($config['max_answer_seconds']);
                const MAX_BYTES = @js($config['max_upload_kb'] * 1024);
                const CSRF = @js(csrf_token());
                const live = $('rec-live'), review = $('rec-review'), err = $('rec-error');
                const btn = { start: $('rec-start'), stop: $('rec-stop'), retake: $('rec-retake'), save: $('rec-save') };
                let current = null, stream = null, recorder = null, chunks = [], timer = null, elapsed = 0, pending = null, busy = false;

                const showError = (m) => { err.textContent = m; show(err, !!m); };
                function setState(state) {
                    show($('rec-loading'), state === 'loading');
                    show(live, state === 'ready' || state === 'recording');
                    show(review, state === 'review');
                    show(btn.start, state === 'ready');
                    show(btn.stop, state === 'recording');
                    show(btn.retake, state === 'review');
                    show(btn.save, state === 'review');
                    showFlex($('rec-badge'), state === 'recording');
                    if (state !== 'recording') $('rec-progress').style.width = '0';
                }
                const stopStream = () => { stream?.getTracks().forEach((t) => t.stop()); stream = null; };

                function open(trigger) {
                    current = { uploadUrl: trigger.dataset.uploadUrl };
                    $('rec-label').textContent = `Câu ${trigger.dataset.position}`;
                    $('rec-question').textContent = trigger.dataset.question;
                    $('rec-hint').textContent = trigger.dataset.hint ? 'Gợi ý: ' + trigger.dataset.hint : '';
                    showError('');
                    show($('rec-upload-progress'), false);
                    modal.classList.replace('hidden', 'flex');
                    document.body.style.overflow = 'hidden';
                }
                function close() {
                    if (busy) return;
                    clearInterval(timer);
                    if (recorder?.state === 'recording') { recorder.onstop = null; recorder.stop(); }
                    stopStream();
                    review.pause();
                    if (review.src) URL.revokeObjectURL(review.src);
                    review.removeAttribute('src');
                    pending = null;
                    modal.classList.replace('flex', 'hidden');
                    document.body.style.overflow = '';
                }

                async function startCamera() {
                    setState('loading');
                    $('rec-meta').textContent = `Tối đa ${MAX_SECONDS / 60} phút, tự dừng khi hết giờ.`;
                    if (!supported()) { setState('none'); return showError('Trình duyệt này không hỗ trợ quay video. Hãy dùng "Tải video từ máy".'); }
                    try {
                        stream = await openStream();
                        live.srcObject = stream;
                        setState('ready');
                    } catch (e) {
                        setState('none');
                        showError(mediaError(e));
                    }
                }

                document.querySelectorAll('[data-record]').forEach((b) => b.addEventListener('click', () => { open(b); startCamera(); }));

                btn.start.addEventListener('click', async () => {
                    show(btn.start, false);
                    const cd = $('rec-countdown');
                    cd.classList.replace('hidden', 'grid');
                    for (const n of [3, 2, 1]) { cd.textContent = n; await new Promise((r) => setTimeout(r, 1000)); }
                    cd.classList.replace('grid', 'hidden');
                    if (!stream) return;

                    chunks = [];
                    recorder = newRecorder(stream);
                    recorder.ondataavailable = (e) => e.data.size && chunks.push(e.data);
                    recorder.onstop = () => {
                        const type = (recorder.mimeType || mimeType || 'video/webm').split(';')[0];
                        pending = { blob: new Blob(chunks, { type }), name: `answer.${type === 'video/mp4' ? 'mp4' : 'webm'}`, duration: elapsed, source: 'record' };
                        stopStream();
                        review.src = URL.createObjectURL(pending.blob);
                        setState('review');
                        $('rec-meta').textContent = `Đã quay ${fmt(elapsed)}. Xem lại rồi bấm "Lưu câu trả lời".`;
                    };
                    recorder.start(1000);
                    elapsed = 0;
                    $('rec-time').textContent = '0:00';
                    setState('recording');
                    timer = setInterval(() => {
                        elapsed++;
                        $('rec-time').textContent = fmt(elapsed);
                        $('rec-progress').style.width = Math.min(100, elapsed / MAX_SECONDS * 100) + '%';
                        if (elapsed >= MAX_SECONDS) stop();
                    }, 1000);
                });
                function stop() { clearInterval(timer); if (recorder?.state === 'recording') recorder.stop(); }
                btn.stop.addEventListener('click', () => {
                    if (elapsed < 2) return showError('Hãy quay ít nhất vài giây trước khi dừng.');
                    showError('');
                    stop();
                });
                btn.retake.addEventListener('click', () => {
                    if (review.src) URL.revokeObjectURL(review.src);
                    review.removeAttribute('src');
                    pending = null;
                    showError('');
                    startCamera();
                });
                $('rec-close').addEventListener('click', close);
                modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });

                // ---- Upload a file for one answer ----
                const fileInput = $('answer-file-input');
                let fileTarget = null;
                function readDuration(url) {
                    return new Promise((resolve, reject) => {
                        const probe = document.createElement('video');
                        probe.preload = 'metadata';
                        probe.muted = true;
                        probe.onerror = () => reject(new Error('unreadable'));
                        probe.onloadedmetadata = () => {
                            if (Number.isFinite(probe.duration)) return resolve(probe.duration);
                            // Some WebM files only report their length after seeking to the end.
                            probe.ontimeupdate = () => { probe.ontimeupdate = null; resolve(probe.duration); };
                            probe.currentTime = 1e101;
                        };
                        probe.src = url;
                    });
                }
                document.querySelectorAll('[data-upload-file]').forEach((b) => b.addEventListener('click', () => {
                    fileTarget = document.querySelector(`[data-record="${b.dataset.uploadFile}"]`);
                    fileInput.value = '';
                    fileInput.click();
                }));
                fileInput.addEventListener('change', async () => {
                    const file = fileInput.files[0];
                    if (!file || !fileTarget) return;
                    open(fileTarget);
                    setState('loading');
                    $('rec-loading').textContent = 'Đang đọc video…';
                    const fail = (m) => { setState('none'); showError(m); };
                    if (file.size > MAX_BYTES) return fail(`Video nặng ${(file.size / 1048576).toFixed(1)} MB, vượt giới hạn ${MAX_BYTES / 1048576} MB.`);
                    const url = URL.createObjectURL(file);
                    let duration;
                    try { duration = await readDuration(url); } catch { URL.revokeObjectURL(url); return fail('Trình duyệt không đọc được video này. Hãy chuyển sang MP4 (H.264) rồi thử lại.'); }
                    if (duration > MAX_SECONDS + 1) { URL.revokeObjectURL(url); return fail(`Video dài ${fmt(duration)}, vượt giới hạn ${MAX_SECONDS / 60} phút cho mỗi câu.`); }
                    pending = { blob: file, name: file.name, duration: Math.min(duration, MAX_SECONDS), source: 'upload' };
                    review.src = url;
                    setState('review');
                    show(btn.retake, false);
                    $('rec-meta').textContent = `${file.name} · ${fmt(duration)} · ${(file.size / 1048576).toFixed(1)} MB`;
                });

                // ---- Save ----
                btn.save.addEventListener('click', () => {
                    if (!pending || busy) return;
                    if (pending.blob.size > MAX_BYTES) return showError(`Video nặng ${(pending.blob.size / 1048576).toFixed(1)} MB, vượt giới hạn ${MAX_BYTES / 1048576} MB.`);
                    busy = true;
                    btn.save.disabled = btn.retake.disabled = true;
                    showError('');

                    const form = new FormData();
                    form.append('video', pending.blob, pending.name);
                    form.append('duration_seconds', Math.max(1, Math.round(pending.duration)));
                    form.append('source', pending.source);

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', current.uploadUrl);
                    xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.upload.onprogress = (e) => {
                        if (!e.lengthComputable) return;
                        const pct = Math.round(e.loaded / e.total * 100);
                        $('rec-progress-bar').style.width = pct + '%';
                        $('rec-progress-text').textContent = pct + '%';
                    };
                    const failed = (m) => {
                        busy = false;
                        btn.save.disabled = btn.retake.disabled = false;
                        show($('rec-upload-progress'), false);
                        showError(m);
                    };
                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) { busy = false; window.location = JSON.parse(xhr.responseText).redirect; return; }
                        const messages = { 413: 'Video quá lớn so với giới hạn của máy chủ.', 419: 'Phiên đăng nhập đã hết hạn. Hãy tải lại trang rồi thử lại.', 429: 'Bạn lưu quá nhiều lần. Vui lòng đợi một phút.' };
                        let m = messages[xhr.status] || 'Không lưu được video. Vui lòng thử lại.';
                        if (xhr.status === 422) { try { m = Object.values(JSON.parse(xhr.responseText).errors).flat()[0] || m; } catch {} }
                        failed(m);
                    };
                    xhr.onerror = () => failed('Mất kết nối khi tải video lên. Bản cũ vẫn được giữ, vui lòng thử lại.');
                    show($('rec-upload-progress'));
                    xhr.send(form);
                });

                window.addEventListener('beforeunload', stopStream);
            }

            // ================= Step 3: profile =================
            const photoInput = $('photo-input');
            if (photoInput) {
                photoInput.addEventListener('change', () => {
                    const file = photoInput.files[0];
                    if (!file) return;
                    $('photo-preview').src = URL.createObjectURL(file);
                    show($('photo-preview')); show($('photo-placeholder'), false);
                });
                const intro = $('intro');
                const count = () => $('intro-count').textContent = intro.value.length;
                intro.addEventListener('input', count);
                count();
            }

            // ================= Step 4: share =================
            $('copy-share')?.addEventListener('click', async () => {
                const input = $('share-url');
                try { await navigator.clipboard.writeText(input.value); } catch { input.select(); document.execCommand('copy'); }
                $('copy-share').textContent = 'Đã sao chép';
                setTimeout(() => $('copy-share').textContent = 'Sao chép', 2000);
            });
        })();
    </script>
@endsection
