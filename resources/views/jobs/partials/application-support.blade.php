{{--
    "Hỗ trợ ứng tuyển" box on the job page: AI mock interview + AI CV–JD match.
    Guests get login links; employers/admins don't see it.
--}}
@php
    $languages = config('recruitment.mock_interview.languages');
    $activeTab = session('support_tab') ?? old('support_tab') ?? ($errors->has('interview') ? 'interview' : null);
    $selectedCvId = (int) (session('selected_cv_id') ?? old('cv_id') ?? $cvs->first()?->id);
    $tabs = ['interview' => 'Phỏng vấn mô phỏng với AI', 'match' => 'Phân tích CV với việc làm này'];
@endphp

@if (auth()->guest() || $isCandidate)
    <div id="ho-tro-ung-tuyen" class="scroll-mt-24 space-y-4" data-support>
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-slate-900">Hỗ trợ ứng tuyển</h2>
            <div class="mt-4 grid grid-cols-2 gap-3" role="tablist" aria-label="Hỗ trợ ứng tuyển">
                @foreach ($tabs as $key => $label)
                    @guest
                        <a href="{{ route('login') }}" class="grid place-items-center rounded-full border border-blue-600 px-3 py-2 text-center text-xs font-semibold leading-snug text-blue-700 hover:bg-blue-50">{{ $label }}</a>
                    @else
                        <button type="button" role="tab" data-support-tab="{{ $key }}" aria-controls="support-{{ $key }}" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                                class="rounded-full border border-blue-600 px-3 py-2 text-xs font-semibold leading-snug text-blue-700 transition hover:bg-blue-50 aria-selected:border-slate-900 aria-selected:bg-blue-50 aria-selected:ring-1 aria-selected:ring-slate-900">
                            {{ $label }}
                        </button>
                    @endguest
                @endforeach
            </div>
            @guest
                <p class="mt-3 text-xs text-slate-500">Đăng nhập tài khoản ứng viên để luyện phỏng vấn và phân tích CV với AI.</p>
            @endguest
        </div>

        @if ($isCandidate)
            {{-- Shared "upload a CV" form; both panels submit it via their upload buttons. --}}
            <form id="support-upload-form" method="POST" action="{{ route('candidate.cvs.upload') }}" enctype="multipart/form-data" class="hidden">
                @csrf
                <input type="hidden" name="return_to_job" value="{{ $job->id }}">
                <input type="hidden" name="support_tab" value="{{ $activeTab ?? 'match' }}" data-support-upload-tab>
                <input type="file" name="file" accept=".pdf,.doc,.docx" data-support-file onchange="this.form.submit()">
            </form>

            @foreach (array_keys($tabs) as $key)
                <div id="support-{{ $key }}" role="tabpanel" data-support-panel="{{ $key }}" @class(['rounded-2xl border border-slate-200 bg-white p-6', 'hidden' => $activeTab !== $key])>
                    @error('file')
                        <p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror

                    @if ($cvs->isEmpty())
                        <div class="flex items-start gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-600">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Zm0 0v5h5M12 18v-6m-3 3 3-3 3 3"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Chưa có CV</p>
                                <p class="mt-0.5 text-sm text-slate-500">Tải CV ngay tại đây để bắt đầu {{ $key === 'interview' ? 'phỏng vấn mô phỏng' : 'phân tích độ phù hợp với công việc' }}.</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-col items-center rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center">
                            <button type="button" data-support-upload class="btn-primary rounded-full px-6">
                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 0 1-.88-7.9A5 5 0 0 1 15.9 6h.1a5 5 0 0 1 1 9.9M15 13l-3-3-3 3m3-3v12"/></svg>
                                Tải CV lên
                            </button>
                            <p class="mt-3 text-xs text-slate-500">Hỗ trợ: PDF, DOC, DOCX (tối đa {{ (int) (config('recruitment.cv.max_upload_kb') / 1024) }}MB) · AI đọc tốt nhất bản PDF</p>
                            <a href="{{ route('candidate.cvs.create') }}" class="mt-2 text-xs font-semibold text-blue-600 hover:text-blue-700">Hoặc tạo CV trực tuyến →</a>
                        </div>
                    @elseif ($key === 'interview')
                        <form method="POST" action="{{ route('candidate.mock-interviews.store', $job) }}" class="space-y-4" data-busy-text="Đang soạn câu hỏi...">
                            @csrf
                            @error('interview')
                                <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                            <div class="grid items-center gap-2 sm:grid-cols-[auto_1fr] sm:gap-3">
                                <label for="interview-cv" class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <svg class="size-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/></svg>
                                    Chọn CV
                                </label>
                                <select id="interview-cv" name="cv_id" class="form-input">
                                    @foreach ($cvs as $cv)
                                        <option value="{{ $cv->id }}" @selected($cv->id === $selectedCvId)>{{ $cv->title }}{{ ! $cv->isOnline() && ! $cv->isPdf() ? ' (Word — AI chưa đọc được)' : '' }}</option>
                                    @endforeach
                                </select>
                                <label for="interview-language" class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <svg class="size-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.05 9.5A18 18 0 0 1 6.4 9m6.1 9h7M11 21l5-10 5 10M12.75 5C11.8 10.8 8 15.4 3 18"/></svg>
                                    Ngôn ngữ phỏng vấn
                                </label>
                                <select id="interview-language" name="language" class="form-input">
                                    @foreach ($languages as $code => $language)
                                        <option value="{{ $code }}" @selected(old('language', 'vi') === $code)>{{ $language['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" data-support-upload class="btn-secondary rounded-full">
                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Zm-2 15v-6m-3 3 3-3 3 3"/></svg>
                                    Tải CV lên
                                </button>
                                <button type="submit" class="btn-primary rounded-full px-5">Bắt đầu phỏng vấn</button>
                            </div>
                            <p class="text-xs text-slate-500">AI soạn {{ config('recruitment.mock_interview.question_count') }} câu hỏi theo JD và CV của bạn, đọc to từng câu; bạn trả lời bằng giọng nói hoặc gõ chữ, cuối buổi nhận điểm và nhận xét chi tiết.</p>
                        </form>
                    @else
                        <form method="GET" action="{{ route('candidate.cv-match.show', $job) }}" class="space-y-4">
                            <div class="grid items-center gap-2 sm:grid-cols-[auto_1fr] sm:gap-3">
                                <label for="match-cv" class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <svg class="size-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/></svg>
                                    Chọn CV
                                </label>
                                <select id="match-cv" name="cv_id" class="form-input">
                                    @foreach ($cvs as $cv)
                                        <option value="{{ $cv->id }}" @selected($cv->id === $selectedCvId)>{{ $cv->title }}{{ isset($matchScores[$cv->id]) ? ' — '.$matchScores[$cv->id].' điểm' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" data-support-upload class="btn-secondary rounded-full">
                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Zm-2 15v-6m-3 3 3-3 3 3"/></svg>
                                    Tải CV lên
                                </button>
                                <button type="submit" class="btn-primary rounded-full px-5">Phân tích độ phù hợp</button>
                            </div>
                            <p class="text-xs text-slate-500">AI đối chiếu CV với từng yêu cầu trong JD, chấm điểm độ phù hợp và gợi ý cách cải thiện trước khi nộp.</p>
                        </form>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    @if ($isCandidate)
        @push('scripts')
            <script>
                (() => {
                    const root = document.querySelector('[data-support]');
                    const tabs = root.querySelectorAll('[data-support-tab]');
                    const panels = root.querySelectorAll('[data-support-panel]');
                    const uploadTab = root.querySelector('[data-support-upload-tab]');

                    tabs.forEach((tab) => tab.addEventListener('click', () => {
                        const key = tab.dataset.supportTab;
                        const opening = tab.getAttribute('aria-selected') !== 'true';
                        tabs.forEach((t) => t.setAttribute('aria-selected', String(opening && t === tab)));
                        panels.forEach((p) => p.classList.toggle('hidden', !opening || p.dataset.supportPanel !== key));
                        uploadTab.value = key;
                    }));

                    root.querySelectorAll('[data-support-upload]').forEach((button) => button.addEventListener('click', () => {
                        root.querySelector('[data-support-file]').click();
                    }));

                    root.querySelectorAll('form[data-busy-text]').forEach((form) => form.addEventListener('submit', () => {
                        const submit = form.querySelector('[type="submit"]');
                        submit.disabled = true;
                        submit.textContent = form.dataset.busyText;
                    }));
                })();
            </script>
        @endpush
    @endif
@endif
