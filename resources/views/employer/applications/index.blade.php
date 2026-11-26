@extends('layouts.app')
@section('title', 'Kho ứng viên')
@section('subtitle', 'Tìm kiếm CV và quản lý hồ sơ ứng viên đã ứng tuyển.')
@section('icon', 'doc')

@php
    use App\Enums\ApplicationStatus;

    $keywordRules = [
        'must' => ['Trong CV bắt buộc có từ khóa', 'CV phải chứa tất cả các từ khóa này.'],
        'any' => ['Trong CV có một trong các từ khóa', 'CV chỉ cần chứa ít nhất một từ khóa.'],
        'not' => ['Trong CV không có các từ khóa', 'Loại bỏ CV chứa bất kỳ từ khóa nào.'],
    ];
    $info = '<svg class="size-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5m0-8h.01"/></svg>';
@endphp

@section('content')
    <div class="grid gap-4 lg:h-[calc(100vh-8.5rem)] lg:grid-cols-[minmax(300px,360px)_1fr]">
        {{-- ===== Search panel ===== --}}
        <form method="GET" id="cv-search" class="flex min-h-0 flex-col rounded-2xl border border-slate-200 bg-white">
            <h2 class="px-4 pt-4 pb-3 text-xl font-bold text-slate-900">Tìm kiếm CV</h2>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 pb-4">
                <fieldset class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                    <legend class="sr-only">Bộ lọc cơ bản</legend>
                    <p class="font-semibold text-slate-900">Bộ lọc cơ bản</p>
                    <label class="mt-3 block">
                        <span class="flex items-center gap-1 text-[13px] text-slate-600">Từ khóa cần tìm <span title="Tìm trong tên, chức danh, kỹ năng, kinh nghiệm, học vấn của CV">{!! $info !!}</span></span>
                        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nhập từ khóa tự do để lọc CV" class="form-input mt-1.5 bg-white">
                    </label>
                </fieldset>

                <fieldset class="space-y-4 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                    <legend class="sr-only">Bộ lọc chính</legend>
                    <p class="font-semibold text-slate-900">Bộ lọc chính</p>

                    @foreach ($keywordRules as $name => [$label, $hint])
                        <div data-tags="{{ $name }}">
                            <span class="flex items-center gap-1 text-[13px] text-slate-600">{{ $label }} <span title="{{ $hint }}">{!! $info !!}</span></span>
                            <div class="mt-1.5 flex flex-wrap gap-1.5 rounded-lg border border-slate-300 bg-white px-2 py-1.5 focus-within:border-teal-500 focus-within:ring-2 focus-within:ring-teal-500/20">
                                @foreach ($filters[$name] ?? [] as $tag)
                                    <span data-tag class="inline-flex items-center gap-1 rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-800">
                                        {{ $tag }}<input type="hidden" name="{{ $name }}[]" value="{{ $tag }}">
                                        <button type="button" data-remove class="text-teal-600 hover:text-teal-900" aria-label="Bỏ {{ $tag }}">&times;</button>
                                    </span>
                                @endforeach
                                <input type="text" data-tag-input placeholder="Nhập từ khóa và nhấn enter" class="min-w-32 flex-1 border-0 bg-transparent p-1 text-sm outline-none focus:ring-0">
                            </div>
                        </div>
                    @endforeach

                    <label class="block">
                        <span class="text-[13px] text-slate-600">Tỉnh/Thành phố</span>
                        <select name="city" class="form-input mt-1.5 bg-white">
                            <option value="">Tất cả tỉnh/thành</option>
                            @foreach (config('recruitment.locations') as $city)
                                <option @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-[13px] text-slate-600">Vị trí ứng tuyển</span>
                        <select name="job" class="form-input mt-1.5 bg-white">
                            <option value="">Tất cả tin tuyển dụng</option>
                            @foreach ($jobs as $job)
                                <option value="{{ $job->id }}" @selected((int) ($filters['job'] ?? 0) === $job->id)>{{ $job->title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-[13px] text-slate-600">Trạng thái hồ sơ</span>
                        <select name="status" class="form-input mt-1.5 bg-white">
                            <option value="">Tất cả trạng thái</option>
                            @foreach (ApplicationStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="text-[13px] text-slate-600">Giới tính</span>
                            <select name="gender" class="form-input mt-1.5 bg-white">
                                <option value="">Tất cả</option>
                                @foreach (config('recruitment.genders') as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['gender'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-[13px] text-slate-600">Bằng cấp</span>
                            <select name="degree" class="form-input mt-1.5 bg-white">
                                <option value="">Tất cả</option>
                                @foreach (config('recruitment.degrees') as $degree)
                                    <option @selected(($filters['degree'] ?? '') === $degree)>{{ $degree }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </fieldset>
            </div>

            <div class="grid grid-cols-2 gap-3 border-t border-slate-200 p-3">
                <a href="{{ route('employer.applications.index') }}" class="rounded-lg border border-slate-300 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Xóa lọc</a>
                <button class="rounded-lg border border-teal-600 py-2.5 text-sm font-semibold text-teal-700 hover:bg-teal-50">Tìm CV</button>
            </div>
        </form>

        {{-- ===== Candidate list ===== --}}
        <section class="flex min-h-[420px] flex-col rounded-2xl border border-slate-200 bg-white" data-pool>
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 pt-4 pb-3">
                <h2 class="text-xl font-bold text-slate-900">Danh sách ứng viên</h2>
                <a href="{{ route('employer.jobs.create') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-teal-600 px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                    Tạo tin để nhận thêm CV
                </a>
            </div>

            <div class="mx-4 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm">
                <label class="flex items-center gap-2 font-medium text-slate-700">
                    <input type="checkbox" data-select-all class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"> Chọn tất cả
                </label>
                <span class="text-slate-300">·</span>
                <span class="font-semibold text-slate-800">{{ $applications->total() }} CV phù hợp</span>
                <span class="text-slate-300">·</span>
                <span class="text-slate-500">Đã chọn <span data-selected-count class="font-semibold text-teal-700">0</span></span>
                <a href="#" data-send-email aria-disabled="true" class="pointer-events-none ml-auto inline-flex items-center gap-1.5 rounded-lg border border-teal-600 px-4 py-1.5 font-semibold text-teal-700 opacity-40 hover:bg-teal-50">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v12H4V6Zm0 0 8 7 8-7"/></svg>
                    Gửi email
                </a>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3">
                @if ($applications->isEmpty())
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-6 py-10 text-center text-sm text-slate-600">Không tìm thấy ứng viên nào phù hợp.</div>
                @else
                    <ul class="space-y-2.5">
                        @foreach ($applications as $application)
                            @php
                                $candidate = $application->candidate;
                                $profile = $candidate->candidateProfile;
                                $parts = preg_split('/\s+/', trim($candidate->name));
                                $score = $matchScores["{$application->user_id}-{$application->job_post_id}"] ?? null;
                                $isNew = $application->status === ApplicationStatus::Submitted;
                            @endphp
                            <li @class(['flex gap-3 rounded-xl border p-3 transition hover:border-teal-300 hover:shadow-sm', 'border-teal-200 bg-teal-50/30' => $isNew, 'border-slate-200' => ! $isNew])>
                                <input type="checkbox" data-pick value="{{ $candidate->email }}" class="mt-3 size-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500" aria-label="Chọn {{ $candidate->name }}">
                                <span class="relative grid size-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-600 font-bold text-white">
                                    {{ mb_strtoupper(mb_substr(end($parts), 0, 1)) }}
                                    @if ($isNew) <span class="absolute -top-0.5 -right-0.5 size-3 rounded-full border-2 border-white bg-amber-400" title="Chưa xem"></span> @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('employer.applications.show', $application) }}" class="font-semibold text-slate-900 hover:text-teal-700">{{ $candidate->name }}</a>
                                        <span class="{{ $application->status->badgeClasses() }} rounded-full px-2 py-0.5 text-[11px] font-semibold">{{ $application->status->label() }}</span>
                                    </div>
                                    <p class="truncate text-sm text-slate-600">{{ $profile?->headline ?: 'Chưa cập nhật chức danh' }}</p>
                                    <p class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-slate-500">
                                        <span>Ứng tuyển: <span class="font-medium text-slate-700">{{ $application->jobPost->title }}</span></span>
                                        @if ($profile?->address) <span>📍 {{ $profile->address }}</span> @endif
                                        <span>{{ $application->created_at->diffForHumans() }}</span>
                                    </p>
                                    @if (! empty($profile?->skills))
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach (array_slice($profile->skills, 0, 5) as $skill)
                                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="flex shrink-0 flex-col items-end justify-between gap-2">
                                    @if ($score !== null)
                                        <span @class(['rounded-lg px-2 py-1 text-xs font-bold', 'bg-teal-50 text-teal-700' => $score >= 75, 'bg-amber-50 text-amber-700' => $score >= 50 && $score < 75, 'bg-red-50 text-red-600' => $score < 50]) title="Độ phù hợp do AI đánh giá">AI {{ $score }}%</span>
                                    @else
                                        <span></span>
                                    @endif
                                    <div class="flex gap-1.5 text-sm">
                                        <a href="{{ route('employer.applications.cv', $application) }}" target="_blank" class="rounded-lg border border-slate-200 px-2.5 py-1 font-medium text-slate-600 hover:bg-slate-50">CV</a>
                                        <a href="{{ route('employer.applications.show', $application) }}" class="rounded-lg bg-teal-600 px-3 py-1 font-semibold text-white hover:bg-teal-700">Xem</a>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 px-4 py-3 text-sm">
                <label class="flex items-center gap-2 text-slate-600">Hiển thị
                    <select form="cv-search" name="per_page" class="form-input w-20 py-1.5" onchange="this.form.submit()">
                        @foreach ([10, 20, 50] as $size)
                            <option @selected((int) ($filters['per_page'] ?? 20) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </label>
                <nav class="flex items-center gap-1" aria-label="Phân trang">
                    <a href="{{ $applications->previousPageUrl() ?? '#' }}" @class(['grid size-9 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50', 'pointer-events-none opacity-40' => $applications->onFirstPage()])>&lsaquo;</a>
                    @foreach ($applications->getUrlRange(max(1, $applications->currentPage() - 2), min($applications->lastPage(), $applications->currentPage() + 2)) as $page => $url)
                        <a href="{{ $url }}" @class(['grid size-9 place-items-center rounded-lg font-semibold', 'bg-teal-600 text-white' => $page === $applications->currentPage(), 'text-slate-600 hover:bg-slate-100' => $page !== $applications->currentPage()])>{{ $page }}</a>
                    @endforeach
                    <a href="{{ $applications->nextPageUrl() ?? '#' }}" @class(['grid size-9 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50', 'pointer-events-none opacity-40' => ! $applications->hasMorePages()])>&rsaquo;</a>
                </nav>
            </div>
        </section>
    </div>

    <script>
        // Keyword tag inputs: Enter adds a chip carrying a hidden input; × removes it.
        document.querySelectorAll('[data-tags]').forEach((box) => {
            const name = box.dataset.tags;
            const input = box.querySelector('[data-tag-input]');
            const add = () => {
                const value = input.value.trim();
                if (!value) return;
                const chip = document.createElement('span');
                chip.dataset.tag = '';
                chip.className = 'inline-flex items-center gap-1 rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-800';
                chip.append(value);
                const hidden = Object.assign(document.createElement('input'), { type: 'hidden', name: name + '[]', value });
                const remove = Object.assign(document.createElement('button'), { type: 'button', className: 'text-teal-600 hover:text-teal-900', textContent: '×' });
                remove.dataset.remove = '';
                chip.append(hidden, remove);
                input.before(chip);
                input.value = '';
            };
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); add(); }
                if (e.key === 'Backspace' && !input.value) input.previousElementSibling?.remove();
            });
            input.addEventListener('blur', add);
            box.addEventListener('click', (e) => { if (e.target.closest('[data-remove]')) e.target.closest('[data-tag]').remove(); });
        });

        // Selection + "Gửi email" (opens the mail client with selected candidates in BCC).
        const pool = document.querySelector('[data-pool]');
        const picks = [...pool.querySelectorAll('[data-pick]')];
        const all = pool.querySelector('[data-select-all]');
        const send = pool.querySelector('[data-send-email]');
        const sync = () => {
            const chosen = picks.filter((p) => p.checked).map((p) => p.value);
            pool.querySelector('[data-selected-count]').textContent = chosen.length;
            all.checked = picks.length > 0 && chosen.length === picks.length;
            send.classList.toggle('opacity-40', !chosen.length);
            send.classList.toggle('pointer-events-none', !chosen.length);
            send.href = chosen.length ? 'mailto:?bcc=' + encodeURIComponent([...new Set(chosen)].join(',')) : '#';
        };
        all.addEventListener('change', () => { picks.forEach((p) => (p.checked = all.checked)); sync(); });
        picks.forEach((p) => p.addEventListener('change', sync));
    </script>
@endsection
