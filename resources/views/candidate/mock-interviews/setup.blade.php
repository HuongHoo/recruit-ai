@extends('layouts.public')
@section('title', 'Kiểm tra thiết bị')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-slate-800">Trang chủ</a> <span aria-hidden="true">›</span>
            <a href="{{ route('candidate.mock-interviews.index') }}" class="hover:text-slate-800">Luyện phỏng vấn</a> <span aria-hidden="true">›</span>
            <a href="{{ route('jobs.show', $job) }}" class="max-w-xs truncate hover:text-slate-800">{{ $job->title }}</a> <span aria-hidden="true">›</span>
            <span class="font-medium text-slate-800">Kiểm tra thiết bị</span>
        </nav>

        <h1 class="mt-6 text-2xl font-bold text-slate-900 sm:text-3xl">Kiểm tra thiết bị trước khi bắt đầu</h1>
        <p class="mt-2 text-slate-600">Chỉ mất một chút thời gian để đảm bảo AI nghe rõ bạn.</p>
        <p class="mt-2 text-sm font-medium text-slate-700">{{ $job->title }} · {{ count($interview->questions) }} câu hỏi · {{ $interview->languageName() }}</p>

        {{-- Interviewer --}}
        <fieldset class="mt-8">
            <legend class="text-lg font-semibold text-slate-900">Chọn người phỏng vấn</legend>
            <p class="mt-1 text-sm text-slate-600">Nhân vật AI sẽ chào hỏi, đặt câu hỏi và phản hồi bạn như một buổi phỏng vấn trực tuyến thật.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach (array_keys(config('recruitment.mock_interview.interviewers')) as $gender)
                    @php($person = $interview->interviewer($gender))
                    <label class="group cursor-pointer">
                        <input type="radio" name="interviewer" value="{{ $gender }}" class="peer sr-only" @checked($loop->first)>
                        <span class="flex items-center gap-4 rounded-2xl border-2 border-slate-200 bg-white p-3 transition peer-checked:border-blue-600 peer-checked:bg-blue-50/60 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400 hover:border-slate-300">
                            <span class="relative aspect-video w-36 shrink-0 overflow-hidden rounded-xl sm:w-44">
                                @include('candidate.mock-interviews.partials.interviewer-avatar', ['gender' => $gender, 'name' => $person['name'], 'uid' => "iv-{$gender}", 'class' => 'absolute inset-0 size-full'])
                            </span>
                            <span class="min-w-0">
                                <span class="block font-semibold text-slate-900">{{ $person['name'] }}</span>
                                <span class="block text-sm text-slate-600">{{ $person['title'] }}</span>
                                <span class="mt-1 block text-xs text-slate-500">Giọng {{ mb_strtolower(config("recruitment.mock_interview.interviewers.{$gender}.label")) }} · {{ $interview->languageName() }}</span>
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            {{-- Camera --}}
            <div class="space-y-4">
                <div class="relative aspect-video overflow-hidden rounded-2xl bg-slate-900 shadow-lg">
                    <video id="camera-preview" autoplay playsinline muted class="size-full -scale-x-100 object-cover"></video>
                    <div id="camera-off" class="absolute inset-0 hidden flex-col items-center justify-center gap-2 text-sm text-slate-300">
                        <svg class="size-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M15 10l4.55-2.28A1 1 0 0 1 21 8.62v6.76a1 1 0 0 1-.45.84M9.88 5H13a2 2 0 0 1 2 2v3.12M15 15v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2"/></svg>
                        <span id="camera-off-text">Camera đang tắt</span>
                    </div>
                    <span id="camera-live" class="absolute top-3 left-3 hidden items-center gap-1.5 rounded-full bg-black/60 px-2.5 py-1 text-xs font-semibold text-white"><span class="size-2 rounded-full bg-emerald-400"></span> Trực tiếp</span>
                </div>
                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">Camera</span>
                        <span class="block text-xs text-slate-500">Không bắt buộc cho buổi luyện này · hình ảnh không được ghi lại</span>
                    </span>
                    <input id="camera-toggle" type="checkbox" class="peer sr-only" checked>
                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-300 transition peer-checked:bg-blue-600 after:absolute after:top-0.5 after:left-0.5 after:size-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                </label>
            </div>

            {{-- Microphone --}}
            <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-white to-amber-50/60 p-6">
                <h2 class="text-xl font-semibold text-slate-900">Kiểm tra micro</h2>
                <p class="mt-1 text-sm text-slate-600">Hãy nói câu bên dưới. Nếu thanh âm thanh chuyển động, micro đang thu được giọng bạn.</p>

                <label for="mic-select" class="mt-6 block text-xs font-semibold tracking-wide text-slate-500 uppercase">Thiết bị đầu vào</label>
                <select id="mic-select" class="form-input mt-2"><option>Đang tìm micro...</option></select>

                <div class="mt-5 rounded-xl bg-slate-100 px-4 py-6 text-center">
                    <p class="text-sm font-semibold text-slate-800">Đọc thử</p>
                    <p class="mt-1 text-slate-700" lang="{{ $interview->speechLang() }}">“{{ $testPhrase }}”</p>
                    <div id="mic-bars" class="mx-auto mt-5 flex h-10 w-56 items-center justify-center gap-[3px]" aria-hidden="true">
                        @for ($i = 0; $i < 32; $i++)
                            <span class="h-1 w-1 rounded-full bg-blue-600 transition-[height] duration-75"></span>
                        @endfor
                    </div>
                    <p id="mic-status" class="mt-3 text-sm font-medium text-slate-500">Đang xin quyền dùng micro...</p>
                </div>

                <div id="speech-warning" class="mt-4 hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Trình duyệt này chưa hỗ trợ nhận giọng nói. Hãy dùng <strong>Google Chrome</strong> hoặc <strong>Microsoft Edge</strong>, hoặc bạn vẫn có thể gõ câu trả lời.
                </div>
                <p class="mt-4 text-xs text-slate-500">Lưu ý: nhận giọng nói dùng micro mặc định của hệ thống. Hãy đeo tai nghe để AI không nghe nhầm tiếng đọc câu hỏi.</p>

                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <a href="{{ route('jobs.show', $job) }}" class="btn-secondary rounded-full">Quay lại</a>
                    <a id="start-link" href="{{ route('candidate.mock-interviews.session', $interview) }}" class="btn-primary rounded-full px-6">Bắt đầu phỏng vấn →</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const video = document.getElementById('camera-preview');
            const cameraToggle = document.getElementById('camera-toggle');
            const cameraOff = document.getElementById('camera-off');
            const cameraOffText = document.getElementById('camera-off-text');
            const cameraLive = document.getElementById('camera-live');
            const micSelect = document.getElementById('mic-select');
            const micStatus = document.getElementById('mic-status');
            const bars = [...document.querySelectorAll('#mic-bars span')];
            let cameraStream = null;
            let micStream = null;
            let audioContext = null;
            let frame = null;

            const store = (key, value) => { try { sessionStorage.setItem(key, value); } catch (e) {} };
            const read = (key) => { try { return sessionStorage.getItem(key); } catch (e) { return null; } };

            // Remember the chosen interviewer and pass it to the interview page.
            const startLink = document.getElementById('start-link');
            const radios = [...document.querySelectorAll('input[name="interviewer"]')];
            const syncInterviewer = () => {
                const chosen = radios.find((r) => r.checked)?.value;
                if (!chosen) return;
                const url = new URL(startLink.href);
                url.searchParams.set('interviewer', chosen);
                startLink.href = url.toString();
                store('mockInterviewer', chosen);
            };
            radios.forEach((r) => {
                r.checked = r.value === (read('mockInterviewer') ?? radios[0].value);
                r.addEventListener('change', syncInterviewer);
            });
            syncInterviewer();

            if (!('SpeechRecognition' in window || 'webkitSpeechRecognition' in window)) {
                document.getElementById('speech-warning').classList.remove('hidden');
            }

            if (!navigator.mediaDevices?.getUserMedia) {
                micStatus.textContent = 'Trình duyệt không cho truy cập micro/camera (cần mở trang qua HTTPS hoặc localhost).';
                cameraOff.classList.replace('hidden', 'flex');
                cameraOffText.textContent = 'Không truy cập được camera';
                return;
            }

            function showCamera(on) {
                cameraOff.classList.toggle('hidden', on);
                cameraOff.classList.toggle('flex', !on);
                cameraLive.classList.toggle('hidden', !on);
                cameraLive.classList.toggle('flex', on);
            }

            async function startCamera() {
                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    video.srcObject = cameraStream;
                    showCamera(true);
                } catch (e) {
                    cameraToggle.checked = false;
                    cameraOffText.textContent = 'Không bật được camera — bạn vẫn có thể phỏng vấn bình thường';
                    showCamera(false);
                }
                store('mockInterviewCamera', cameraToggle.checked ? '1' : '0');
            }

            function stopCamera() {
                cameraStream?.getTracks().forEach((t) => t.stop());
                cameraStream = null;
                cameraOffText.textContent = 'Camera đang tắt';
                showCamera(false);
                store('mockInterviewCamera', '0');
            }

            cameraToggle.addEventListener('change', () => cameraToggle.checked ? startCamera() : stopCamera());

            async function startMic(deviceId) {
                cancelAnimationFrame(frame);
                micStream?.getTracks().forEach((t) => t.stop());
                await audioContext?.close();

                try {
                    micStream = await navigator.mediaDevices.getUserMedia({ audio: deviceId ? { deviceId: { exact: deviceId } } : true });
                } catch (e) {
                    micStatus.textContent = 'Chưa có quyền dùng micro. Hãy bấm biểu tượng ổ khóa trên thanh địa chỉ và cho phép micro.';
                    micStatus.className = 'mt-3 text-sm font-medium text-red-600';
                    return;
                }

                audioContext = new AudioContext();
                const analyser = audioContext.createAnalyser();
                analyser.fftSize = 64;
                audioContext.createMediaStreamSource(micStream).connect(analyser);
                const data = new Uint8Array(analyser.frequencyBinCount);
                micStatus.textContent = 'Hãy đọc câu trên...';

                const draw = () => {
                    analyser.getByteFrequencyData(data);
                    let peak = 0;
                    bars.forEach((bar, i) => {
                        const value = data[i % data.length] / 255;
                        peak = Math.max(peak, value);
                        bar.style.height = `${Math.max(4, value * 40)}px`;
                    });
                    if (peak > 0.35) {
                        micStatus.textContent = 'Micro đang thu âm ✓';
                        micStatus.className = 'mt-3 text-sm font-medium text-blue-600';
                    }
                    frame = requestAnimationFrame(draw);
                };
                draw();

                const devices = (await navigator.mediaDevices.enumerateDevices()).filter((d) => d.kind === 'audioinput');
                const current = micStream.getAudioTracks()[0]?.getSettings().deviceId;
                micSelect.innerHTML = '';
                devices.forEach((d, i) => micSelect.add(new Option(d.label || `Micro ${i + 1}`, d.deviceId, false, d.deviceId === current)));
            }

            micSelect.addEventListener('change', () => startMic(micSelect.value));

            if (read('mockInterviewCamera') === '0') {
                cameraToggle.checked = false;
                showCamera(false);
            } else {
                startCamera();
            }
            startMic();

            // Release devices before moving on; the interview page asks again for what it needs.
            startLink.addEventListener('click', () => {
                store('mockInterviewCamera', cameraToggle.checked ? '1' : '0');
                cameraStream?.getTracks().forEach((t) => t.stop());
                micStream?.getTracks().forEach((t) => t.stop());
            });
        })();
    </script>
@endpush
