@extends('layouts.public')
@section('title', 'Phỏng vấn mô phỏng')
@section('hide-chat-assistant', '1')

@section('content')
    <div class="bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('candidate.mock-interviews.index') }}" class="hover:text-slate-800">Luyện phỏng vấn</a> <span aria-hidden="true">›</span>
                <a href="{{ route('jobs.show', $job) }}" class="max-w-xs truncate hover:text-slate-800">{{ $job->title }}</a> <span aria-hidden="true">›</span>
                <span class="font-medium text-slate-800">Phỏng vấn</span>
            </nav>

            <div class="mt-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Phỏng vấn mô phỏng với AI</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ $job->title }} · {{ $job->company_name }} · {{ $interview->languageName() }}</p>
                </div>
                <p class="text-sm font-semibold text-slate-700">Câu <span data-current-number>1</span> / {{ count($interview->questions) }}</p>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                <div data-progress class="h-full rounded-full bg-blue-600 transition-all" style="width: 0%"></div>
            </div>

            @error('interview')
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }} Câu trả lời của bạn vẫn được giữ nguyên.</div>
            @enderror

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_300px]">
                <div class="space-y-5">
                    {{-- Video call with the AI interviewer --}}
                    <section class="overflow-hidden rounded-2xl bg-slate-900 shadow-xl">
                        <div data-stage class="relative aspect-video">
                            @include('candidate.mock-interviews.partials.interviewer-avatar', ['gender' => $interviewer['gender'], 'name' => $interviewer['name'], 'uid' => 'iv-main', 'class' => 'absolute inset-0 size-full', 'nameplate' => false])

                            <div class="absolute top-3 left-3 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-black/55 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur">
                                    <span class="size-2 animate-pulse rounded-full bg-red-500"></span>
                                    <span data-timer>00:00</span>
                                </span>
                                <span data-state class="hidden rounded-full bg-black/55 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur"></span>
                            </div>

                            <div class="absolute bottom-3 left-3 max-w-[55%] rounded-lg bg-black/55 px-3 py-1.5 text-white backdrop-blur">
                                <p class="truncate text-sm font-semibold">{{ $interviewer['name'] }}</p>
                                <p class="truncate text-[11px] text-slate-200">{{ $interviewer['title'] }} · Nhà tuyển dụng AI</p>
                            </div>

                            {{-- Candidate picture-in-picture --}}
                            <div class="absolute right-3 bottom-3 aspect-video w-28 overflow-hidden rounded-lg bg-slate-700 shadow-lg ring-2 ring-white/70 sm:w-44">
                                <video data-camera autoplay playsinline muted class="hidden size-full -scale-x-100 object-cover"></video>
                                <div data-camera-off class="grid size-full place-items-center">
                                    <span class="grid size-10 place-items-center rounded-full bg-slate-500 text-sm font-bold text-white sm:size-12">{{ mb_strtoupper(mb_substr(\Illuminate\Support\Str::afterLast(trim(auth()->user()->name), ' '), 0, 1)) }}</span>
                                </div>
                                <span class="absolute bottom-1 left-1.5 rounded bg-black/55 px-1.5 text-[10px] font-semibold text-white">Bạn</span>
                                <span data-mic-badge class="absolute top-1 right-1 hidden size-2.5 animate-pulse rounded-full bg-red-500"></span>
                            </div>
                        </div>

                        {{-- Captions --}}
                        <div class="border-t border-white/10 px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span data-focus class="rounded-full bg-blue-500/20 px-3 py-1 text-xs font-semibold text-blue-200"></span>
                                <button type="button" data-replay class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-300 hover:text-blue-200">
                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6 9H2v6h4l5 4V5Zm4.54 3.46a5 5 0 0 1 0 7.07M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                                    Nghe lại câu hỏi
                                </button>
                            </div>
                            <p data-caption class="mt-3 min-h-14 text-lg leading-relaxed font-medium text-white" lang="{{ $interview->speechLang() }}" aria-live="polite"></p>
                            <p data-voice-note class="mt-2 hidden text-xs text-amber-200"></p>
                        </div>
                    </section>

                    {{-- Answer --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6">
                        <div class="flex items-center justify-between gap-3">
                            <label for="answer" class="font-semibold text-slate-900">Câu trả lời của bạn</label>
                            <span data-save-state class="text-xs text-slate-400"></span>
                        </div>
                        <textarea id="answer" data-answer rows="7" maxlength="{{ config('recruitment.mock_interview.max_answer_chars') }}" class="form-input mt-3 leading-relaxed" lang="{{ $interview->speechLang() }}"
                                  placeholder="Bấm nút micro và nói câu trả lời, hoặc gõ trực tiếp vào đây..."></textarea>

                        <div class="mt-4 flex flex-col items-center gap-2">
                            <button type="button" data-mic class="group grid size-16 place-items-center rounded-full bg-blue-600 text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 data-[listening=true]:bg-red-600 data-[listening=true]:shadow-red-600/30" aria-label="Bắt đầu trả lời bằng giọng nói">
                                <svg class="size-7 group-data-[listening=true]:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Zm7 9a7 7 0 0 1-14 0m7 7v3"/></svg>
                                <span class="hidden size-5 rounded-sm bg-white group-data-[listening=true]:block"></span>
                            </button>
                            <p data-mic-status class="text-sm text-slate-500">Bấm để trả lời bằng giọng nói</p>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                            <button type="button" data-prev class="btn-secondary rounded-full">← Câu trước</button>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" data-next class="btn-primary rounded-full px-5">Câu tiếp theo →</button>
                                <button type="button" data-finish class="btn-primary hidden rounded-full bg-emerald-600 px-5 hover:bg-emerald-700">Kết thúc &amp; chấm điểm</button>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-5">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 class="text-sm font-semibold text-slate-900">Danh sách câu hỏi</h2>
                        <ol data-question-list class="mt-3 space-y-1.5 text-sm"></ol>
                        <button type="button" data-finish-early class="mt-4 w-full text-center text-xs font-semibold text-slate-500 hover:text-slate-800">Kết thúc sớm và chấm điểm</button>
                    </section>

                    <section class="rounded-2xl border border-blue-100 bg-blue-50 p-5 text-sm text-slate-700">
                        <h2 class="font-semibold text-slate-900">Mẹo trả lời</h2>
                        <ul class="mt-2 list-disc space-y-1 pl-4">
                            <li>Trả lời theo cấu trúc STAR: Tình huống – Nhiệm vụ – Hành động – Kết quả.</li>
                            <li>Nêu ví dụ, con số cụ thể từ kinh nghiệm của bạn.</li>
                            <li>Có thể sửa lại chữ sau khi nói xong trước khi sang câu tiếp.</li>
                        </ul>
                    </section>
                </aside>
            </div>
        </div>
    </div>

    {{-- Start overlay: browsers only allow speech after a user click. --}}
    <div data-start-overlay class="fixed inset-0 z-40 grid place-items-center bg-slate-900/60 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-2xl">
            <div class="relative mx-auto size-24">
                <div class="size-full overflow-hidden rounded-full ring-4 ring-blue-100">
                    @include('candidate.mock-interviews.partials.interviewer-avatar', ['gender' => $interviewer['gender'], 'name' => $interviewer['name'], 'uid' => 'iv-thumb', 'class' => 'size-full'])
                </div>
                <span class="absolute right-1 bottom-1 size-4 rounded-full bg-emerald-500 ring-2 ring-white"></span>
            </div>
            <h2 class="mt-4 text-xl font-bold text-slate-900">{{ $interviewer['name'] }} đã vào phòng</h2>
            <p class="text-sm text-slate-500">{{ $interviewer['title'] }} · {{ $job->company_name }}</p>
            <p class="mt-3 text-sm text-slate-600">Buổi phỏng vấn gồm {{ count($interview->questions) }} câu hỏi, người phỏng vấn sẽ nói trực tiếp với bạn. Bật loa hoặc đeo tai nghe, rồi trả lời bằng giọng nói hoặc gõ chữ.</p>
            <button type="button" data-start class="btn-primary mt-6 w-full rounded-full">{{ $interview->answeredCount() > 0 ? 'Vào lại phòng phỏng vấn' : 'Vào phòng phỏng vấn' }}</button>
        </div>
    </div>

    {{-- Grading overlay --}}
    <div data-grading-overlay class="fixed inset-0 z-50 hidden place-items-center bg-white/90 p-4">
        <div class="text-center">
            <span class="mx-auto block size-12 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"></span>
            <p class="mt-4 font-semibold text-slate-900">AI đang chấm điểm buổi phỏng vấn...</p>
            <p class="mt-1 text-sm text-slate-500">Thường mất 10–40 giây, vui lòng không đóng trang.</p>
        </div>
    </div>

    <form data-finish-form method="POST" action="{{ route('candidate.mock-interviews.finish', $interview) }}" class="hidden">
        @csrf
    </form>
@endsection


@push('scripts')
    @php
        $sessionConfig = [
            'questions' => $interview->questions,
            'answers' => $interview->answers,
            'lang' => $interview->speechLang(),
            'saveUrl' => route('candidate.mock-interviews.answer', $interview),
            'speechUrl' => route('candidate.mock-interviews.speech', [$interview, 'LINE', 'interviewer' => $interviewer['gender']]),
            'csrf' => csrf_token(),
            'interviewer' => $interviewer,
            'resuming' => $interview->answeredCount() > 0,
        ];
    @endphp
    <script>
        (() => {
            const config = @json($sessionConfig);
            const iv = config.interviewer;
            const $ = (selector) => document.querySelector(selector);
            const total = config.questions.length;
            const answers = config.answers.map((a) => a ?? '');
            let index = Math.max(0, answers.findIndex((a) => a.trim() === ''));
            if (answers.every((a) => a.trim() !== '')) index = total - 1;

            const textarea = $('[data-answer]');
            const micButton = $('[data-mic]');
            const micStatus = $('[data-mic-status]');
            const saveState = $('[data-save-state]');
            const caption = $('[data-caption]');
            const stateBadge = $('[data-state]');
            const avatar = $('[data-stage] [data-avatar]');
            const mouthOpen = avatar.querySelector('[data-mouth-open]');
            const mouthClosed = avatar.querySelector('[data-mouth-closed]');
            let listening = false;

            /* ---------- Interviewer animation ---------- */
            function setState(text) {
                stateBadge.textContent = text;
                stateBadge.classList.toggle('hidden', !text);
            }
            function setMouth(open) {
                mouthOpen.setAttribute('transform', `translate(0 199) scale(1 ${open}) translate(0 -199)`);
                mouthClosed.style.opacity = open > 0.15 ? '0' : '1';
            }
            let mouthTimer = null;
            let mouthFrame = null;
            // level(): 0..1 loudness of the voice being played; without it the mouth moves at random.
            function startTalking(level = null) {
                avatar.classList.remove('is-listening');
                avatar.classList.add('is-speaking');
                setState('Đang nói...');
                clearInterval(mouthTimer);
                cancelAnimationFrame(mouthFrame);
                if (level) {
                    let open = 0;
                    const follow = () => {
                        open = open * 0.45 + level() * 0.55;
                        setMouth(open < 0.08 ? 0 : Math.min(1, open));
                        mouthFrame = requestAnimationFrame(follow);
                    };
                    follow();
                } else {
                    mouthTimer = setInterval(() => setMouth(Math.random() < 0.2 ? 0.1 : 0.35 + Math.random() * 0.65), 115);
                }
            }
            function stopTalking() {
                clearInterval(mouthTimer);
                cancelAnimationFrame(mouthFrame);
                setMouth(0);
                avatar.classList.remove('is-speaking');
                setState(listening ? 'Đang lắng nghe...' : '');
            }
            function nod() {
                avatar.classList.remove('is-nodding');
                void avatar.getBoundingClientRect();
                avatar.classList.add('is-nodding');
                setTimeout(() => avatar.classList.remove('is-nodding'), 950);
            }

            /* ---------- Interviewer voice ----------
             * Lines are read by the AI voice generated on the server (natural in every interview language).
             * The browser's own voice is only a backup, and only when it really has a voice for this
             * language: an English voice reading Vietnamese text is unintelligible.
             */
            const synth = window.speechSynthesis;
            const voiceNote = $('[data-voice-note]');
            const tokens = (name) => ` ${name.toLowerCase().replace(/[^\p{L}\p{N}-]+/gu, ' ')} `;
            const hasWord = (name, words) => words.some((w) => tokens(name).includes(` ${w} `));
            let voiceMatchesGender = false;

            function pickVoice() {
                const prefix = config.lang.split('-')[0];
                const sameLang = (synth?.getVoices() ?? []).filter((v) => v.lang.replace('_', '-').toLowerCase().startsWith(prefix));
                const own = sameLang.find((v) => hasWord(v.name, iv.voice) && !hasWord(v.name, iv.avoidVoice));
                voiceMatchesGender = Boolean(own);
                return own || sameLang.find((v) => !hasWord(v.name, iv.avoidVoice)) || sameLang[0] || null;
            }
            function showVoiceNote(text) {
                voiceNote.textContent = text;
                voiceNote.classList.toggle('hidden', !text);
            }

            // Lines: { key, text }; the key names the line for the server ("question-2", "ack-0", ...).
            const line = (key, text) => ({ key, text });
            const questionLine = () => line(`question-${index}`, config.questions[index].question);
            const questionText = () => config.questions[index].question;

            /* Clips are fetched one at a time ahead of need (the AI voice has a per-minute limit). */
            const clips = new Map();
            function clip(key) {
                if (!clips.has(key)) {
                    clips.set(key, fetch(config.speechUrl.replace('LINE', key), { headers: { Accept: 'audio/wav' } })
                        .then((response) => (response.ok ? response.blob() : null))
                        .then((blob) => (blob?.size ? URL.createObjectURL(blob) : null))
                        .catch(() => null)
                        .then((url) => { if (!url) clips.delete(key); return url; }));
                }
                return clips.get(key);
            }
            const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
            const queue = [];
            let prefetching = false;
            function prefetch(keys, urgent = false) {
                keys.forEach((key) => { if (!queue.includes(key)) urgent ? queue.unshift(key) : queue.push(key); });
                if (prefetching) return;
                prefetching = true;
                (async () => {
                    let failures = 0;
                    while (queue.length && failures < 6) {
                        const key = queue[0];
                        if (await clip(key)) { queue.shift(); failures = 0; continue; }
                        failures++;
                        await wait(15000);
                    }
                    prefetching = false;
                })();
            }
            function prefetchRest() {
                const ahead = config.questions.map((_, i) => `question-${(index + i) % total}`);
                prefetch([...ahead, ...iv.acks.map((_, i) => `ack-${i}`), 'skip', 'closing']);
            }

            // Web Audio analyser so the mouth follows the real loudness of the voice.
            let audioContext = null;
            let analyser = null;
            function initAudio() {
                try {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    analyser = audioContext.createAnalyser();
                    analyser.fftSize = 512;
                    analyser.connect(audioContext.destination);
                } catch (e) { audioContext = null; analyser = null; }
            }
            function loudness() {
                const data = new Uint8Array(analyser.fftSize);
                analyser.getByteTimeDomainData(data);
                let sum = 0;
                for (const value of data) sum += ((value - 128) / 128) ** 2;
                return Math.min(1, Math.max(0, (Math.sqrt(sum / data.length) - 0.015) * 7));
            }

            let speechRun = 0;
            let stopCurrent = null;
            function hush() {
                speechRun++;
                stopCurrent?.();
                synth?.cancel();
                stopTalking();
            }

            function playClip(url) {
                return new Promise((resolve) => {
                    const audio = new Audio(url);
                    let done = false;
                    const finish = (ok) => {
                        if (done) return;
                        done = true;
                        stopCurrent = null;
                        audio.pause();
                        resolve(ok);
                    };
                    stopCurrent = () => finish(true);
                    audio.onended = () => finish(true);
                    audio.onerror = () => finish(false);
                    let level = null;
                    if (analyser) {
                        try {
                            audioContext.resume();
                            audioContext.createMediaElementSource(audio).connect(analyser);
                            level = loudness;
                        } catch (e) { level = null; }
                    }
                    audio.play().then(() => startTalking(level)).catch(() => finish(false));
                });
            }

            function speakWithBrowser(text) {
                return new Promise((resolve) => {
                    let done = false;
                    const timers = [];
                    const finish = () => { if (done) return; done = true; stopCurrent = null; timers.forEach(clearTimeout); resolve(); };
                    stopCurrent = finish;
                    const estimated = Math.min(25000, 800 + text.length * (/[぀-鿿]/.test(text) ? 190 : 65));
                    const voice = synth ? pickVoice() : null;
                    startTalking();

                    if (!voice) {
                        // Nothing that can speak this language: give the candidate time to read the caption.
                        showVoiceNote('Chưa phát được giọng đọc lúc này — bạn hãy đọc câu hỏi trong phụ đề. Bấm "Nghe lại câu hỏi" để thử lại.');
                        timers.push(setTimeout(finish, estimated));
                        return;
                    }

                    showVoiceNote('Giọng AI đang bận, tạm dùng giọng đọc của trình duyệt.');
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = config.lang;
                    utterance.voice = voice;
                    utterance.rate = 0.95;
                    // No voice of the right gender: shift the default one a little.
                    utterance.pitch = voiceMatchesGender ? 1 : (iv.gender === 'male' ? 0.8 : 1.15);
                    utterance.onend = utterance.onerror = finish;
                    // Chrome occasionally never fires onend.
                    timers.push(setTimeout(finish, estimated * 2.5 + 3000));
                    synth.speak(utterance);
                });
            }

            async function speakOne({ key, text }) {
                const run = speechRun;
                setState('Đang chuẩn bị...');
                prefetch([key], true);
                const url = await Promise.race([clip(key), wait(12000).then(() => null)]);
                if (run !== speechRun) return;
                if (url && await playClip(url)) { showVoiceNote(''); return; }
                if (run !== speechRun) return;
                await speakWithBrowser(text);
            }

            async function say(lines) {
                hush();
                const run = speechRun;
                for (const item of lines) {
                    caption.textContent = item.text;
                    await speakOne(item);
                    if (run !== speechRun) return false;
                }
                stopTalking();
                caption.textContent = questionText();
                return true;
            }

            /* ---------- Speech-to-text ---------- */
            const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            let recognition = null;
            let base = '';
            let committed = '';

            function setListening(on) {
                listening = on;
                micButton.dataset.listening = String(on);
                textarea.readOnly = on;
                micStatus.textContent = on ? 'Đang nghe... bấm để dừng' : 'Bấm để trả lời bằng giọng nói';
                micStatus.className = on ? 'text-sm font-semibold text-red-600' : 'text-sm text-slate-500';
                avatar.classList.toggle('is-listening', on);
                $('[data-mic-badge]').classList.toggle('hidden', !on);
                setState(on ? 'Đang lắng nghe...' : '');
            }

            if (Recognition) {
                recognition = new Recognition();
                recognition.lang = config.lang;
                recognition.continuous = true;
                recognition.interimResults = true;

                recognition.onresult = (event) => {
                    let interim = '';
                    let finalText = '';
                    for (let i = 0; i < event.results.length; i++) {
                        const text = event.results[i][0].transcript;
                        event.results[i].isFinal ? (finalText += text) : (interim += text);
                    }
                    committed = finalText;
                    textarea.value = (base + finalText + interim).trimStart();
                    scheduleSave();
                };
                recognition.onerror = (event) => {
                    if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                        setListening(false);
                        micStatus.textContent = 'Chưa có quyền dùng micro — hãy cho phép micro hoặc gõ câu trả lời.';
                        micStatus.className = 'text-sm text-red-600';
                    }
                };
                // Chrome stops after a pause; keep listening until the candidate presses stop.
                recognition.onend = () => {
                    if (!listening) return;
                    base = (base + committed).trimEnd() + ' ';
                    committed = '';
                    try { recognition.start(); } catch (e) { setListening(false); }
                };
            } else {
                micButton.disabled = true;
                micButton.classList.add('opacity-40', 'cursor-not-allowed');
                micStatus.textContent = 'Trình duyệt chưa hỗ trợ nhận giọng nói (hãy dùng Chrome/Edge) — bạn có thể gõ câu trả lời.';
            }

            function startListening() {
                hush();
                caption.textContent = questionText();
                base = textarea.value.trim() ? textarea.value.trimEnd() + ' ' : '';
                committed = '';
                setListening(true);
                try { recognition.start(); } catch (e) { /* already started */ }
            }
            function stopListening() {
                if (!listening) return;
                setListening(false);
                recognition.stop();
                saveNow();
                if (textarea.value.trim()) nod();
            }
            micButton.addEventListener('click', () => listening ? stopListening() : startListening());

            /* ---------- Autosave ---------- */
            let saveTimer = null;
            function scheduleSave() {
                answers[index] = textarea.value;
                saveState.textContent = 'Đang lưu...';
                clearTimeout(saveTimer);
                saveTimer = setTimeout(saveNow, 1200);
            }
            function saveNow(i = index) {
                clearTimeout(saveTimer);
                answers[i] = i === index ? textarea.value : answers[i];
                return fetch(config.saveUrl, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    body: JSON.stringify({ index: i, answer: answers[i] }),
                }).then((response) => {
                    saveState.textContent = response.ok ? 'Đã lưu' : 'Chưa lưu được — câu trả lời vẫn được gửi khi kết thúc';
                }).catch(() => { saveState.textContent = 'Mất kết nối — câu trả lời vẫn được gửi khi kết thúc'; });
            }
            textarea.addEventListener('input', scheduleSave);

            /* ---------- Navigation ---------- */
            function render() {
                const question = config.questions[index];
                $('[data-current-number]').textContent = index + 1;
                caption.textContent = question.question;
                $('[data-focus]').textContent = question.focus || `Câu ${index + 1}`;
                textarea.value = answers[index];
                saveState.textContent = '';
                $('[data-prev]').disabled = index === 0;
                $('[data-prev]').classList.toggle('invisible', index === 0);
                $('[data-next]').classList.toggle('hidden', index === total - 1);
                $('[data-finish]').classList.toggle('hidden', index !== total - 1);
                $('[data-progress]').style.width = `${(answers.filter((a) => a.trim()).length / total) * 100}%`;

                const list = $('[data-question-list]');
                list.innerHTML = '';
                config.questions.forEach((q, i) => {
                    const item = document.createElement('li');
                    const button = document.createElement('button');
                    const done = answers[i].trim() !== '';
                    button.type = 'button';
                    button.className = `flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left ${i === index ? 'bg-blue-50 font-semibold text-blue-700' : 'text-slate-600 hover:bg-slate-50'}`;
                    button.innerHTML = `<span class="grid size-5 shrink-0 place-items-center rounded-full text-[10px] font-bold ${done ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600'}">${done ? '✓' : i + 1}</span><span class="truncate"></span>`;
                    button.lastChild.textContent = q.focus || `Câu ${i + 1}`;
                    button.addEventListener('click', () => go(i));
                    item.append(button);
                    list.append(item);
                });
            }
            function go(next) {
                if (next === index || next < 0 || next >= total) return;
                stopListening();
                saveNow();
                const answered = answers[index].trim() !== '';
                const forward = next === index + 1;
                index = next;
                render();
                if (forward) {
                    if (answered) nod();
                    const ack = Math.floor(Math.random() * iv.acks.length);
                    const reaction = answered ? line(`ack-${ack}`, iv.acks[ack]) : line('skip', iv.skip);
                    say([reaction, questionLine()]);
                } else {
                    say([questionLine()]);
                }
            }
            $('[data-prev]').addEventListener('click', () => go(index - 1));
            $('[data-next]').addEventListener('click', () => go(index + 1));
            $('[data-replay]').addEventListener('click', () => { stopListening(); say([questionLine()]); });

            let finishing = false;
            async function finish() {
                if (finishing) return;
                stopListening();
                answers[index] = textarea.value;
                const unanswered = answers.filter((a) => !a.trim()).length;
                if (unanswered === total) {
                    alert('Bạn chưa trả lời câu nào. Hãy trả lời ít nhất một câu để AI chấm điểm.');
                    return;
                }
                if (unanswered > 0 && !confirm(`Còn ${unanswered} câu chưa trả lời (sẽ được 0 điểm). Vẫn kết thúc và chấm điểm?`)) return;
                finishing = true;

                const form = $('[data-finish-form]');
                answers.forEach((answer, i) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `answers[${i}]`;
                    input.value = answer;
                    form.append(input);
                });

                document.querySelectorAll('[data-prev], [data-next], [data-finish], [data-finish-early], [data-mic], [data-replay]').forEach((b) => { b.disabled = true; });
                textarea.readOnly = true;
                // Let the interviewer say goodbye (capped, so a silent browser does not hold things up).
                nod();
                await Promise.race([say([line('closing', iv.closing)]), wait(14000)]);
                hush();
                $('[data-grading-overlay]').classList.replace('hidden', 'grid');
                form.submit();
            }
            $('[data-finish]').addEventListener('click', finish);
            $('[data-finish-early]').addEventListener('click', finish);

            /* ---------- Call timer ---------- */
            function startTimer() {
                const startedAt = Date.now();
                const timer = $('[data-timer]');
                setInterval(() => {
                    const seconds = Math.floor((Date.now() - startedAt) / 1000);
                    timer.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
                }, 1000);
            }

            /* ---------- Start ---------- */
            $('[data-start]').addEventListener('click', async () => {
                $('[data-start-overlay]').remove();
                initAudio(); // inside the click, so the browser allows sound
                startTimer();
                say([config.resuming ? line('resume', iv.resume) : line('greeting', iv.greeting), questionLine()]);
                prefetchRest();

                let wantsCamera = true;
                try { wantsCamera = sessionStorage.getItem('mockInterviewCamera') !== '0'; } catch (e) {}
                if (wantsCamera && navigator.mediaDevices?.getUserMedia) {
                    try {
                        $('[data-camera]').srcObject = await navigator.mediaDevices.getUserMedia({ video: true });
                        $('[data-camera]').classList.remove('hidden');
                        $('[data-camera-off]').classList.add('hidden');
                    } catch (e) { /* camera is optional */ }
                }
            });

            synth?.getVoices();
            render();
            // Start preparing the opening lines while the candidate reads the welcome screen.
            prefetch([config.resuming ? 'resume' : 'greeting', `question-${index}`]);
        })();
    </script>
@endpush
