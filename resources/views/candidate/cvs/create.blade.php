@extends('layouts.app')
@section('title', $cv->exists ? 'Chỉnh sửa CV' : 'Tạo CV')

@php
    $templates = config('recruitment.cv.templates');
    $steps = [
        'personal' => 'Thông tin cá nhân',
        'summary' => 'Tóm tắt',
        'experience' => 'Kinh nghiệm làm việc',
        'education' => 'Học vấn',
        'skills' => 'Kỹ năng',
        'finish' => 'Hoàn tất',
    ];
    // After a failed submit, open the first step that has an error.
    $errorStep = collect([
        'personal' => ['name', 'headline', 'phone', 'address', 'date_of_birth'],
        'summary' => ['objective'],
        'experience' => ['experiences'],
        'education' => ['educations'],
        'skills' => ['skills'],
        'finish' => ['title', 'template', 'accent_color'],
    ])->search(fn (array $fields) => collect($errors->keys())->contains(fn ($key) => collect($fields)->contains(fn ($f) => str_starts_with($key, $f))));
    $currentTemplate = old('template', $cv->template);
@endphp

@section('content')
    <form id="cv-builder" method="POST" action="{{ $cv->exists ? route('candidate.cvs.update', $cv) : route('candidate.cvs.store') }}"
          data-preview-url="{{ route('candidate.cvs.preview') }}" data-start-step="{{ $errorStep ?: 'personal' }}" novalidate>
        @csrf
        @if ($cv->exists) @method('PUT') @endif
        <input type="hidden" name="builder" value="1">
        <input type="hidden" name="template" value="{{ $currentTemplate }}" data-template-input>

        {{-- Top bar --}}
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-3">
            <p class="min-w-0 flex-1 truncate text-sm">
                <a href="{{ route('candidate.cvs.index') }}" class="text-slate-500 hover:text-blue-700">Quản lý CV</a>
                <span class="text-slate-300">/</span>
                <span class="font-semibold text-slate-900" data-title-label>{{ old('title', $cv->title) }}</span>
                <span class="text-slate-400">· <span data-template-label>{{ $templates[$currentTemplate]['name'] ?? '' }}</span></span>
            </p>
            <button type="button" class="btn-secondary py-2" data-open-picker>Đổi mẫu</button>
            <a href="{{ route('candidate.cvs.index') }}#tai-cv" class="btn-secondary py-2">Tải CV lên</a>
            @if ($cv->exists)
                <a href="{{ route('candidate.cvs.show', $cv) }}" target="_blank" class="btn-secondary py-2">Xem trước</a>
                <a href="{{ route('candidate.cvs.download', $cv) }}" target="_blank" class="btn-secondary py-2">Tải xuống</a>
            @endif
            <span class="flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700" data-ats-badge>
                <span data-ats-score>0</span>/100 <span class="font-normal">Điểm ATS</span>
            </span>
            <button type="submit" class="btn-primary py-2">{{ $cv->exists ? 'Lưu CV' : 'Lưu & tạo CV' }}</button>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Vui lòng kiểm tra lại các mục được đánh dấu đỏ.</div>
        @endif

        {{-- Step navigator --}}
        <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-4">
            <ol class="flex gap-2 overflow-x-auto text-sm">
                @foreach ($steps as $key => $label)
                    <li class="shrink-0">
                        <button type="button" data-step-tab="{{ $key }}" class="group flex items-center gap-2 rounded-full px-3 py-1.5 font-medium text-slate-500 hover:bg-slate-50 aria-[current=step]:bg-blue-50 aria-[current=step]:text-blue-700">
                            <span data-step-dot class="grid size-6 place-items-center rounded-full border border-slate-300 text-xs font-bold group-aria-[current=step]:border-blue-600 group-aria-[current=step]:bg-blue-600 group-aria-[current=step]:text-white data-[done]:border-emerald-500 data-[done]:bg-emerald-500 data-[done]:text-white">{{ $loop->iteration }}</span>
                            {{ $label }}
                        </button>
                    </li>
                @endforeach
            </ol>
            <p class="mt-3 text-xs text-slate-500" data-progress></p>
        </div>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            {{-- ================= FORM ================= --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                {{-- 1. Personal --}}
                <section data-step="personal" class="space-y-5">
                    <h2 class="text-lg font-semibold text-slate-900">Thông tin cá nhân</h2>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="form-label">Họ và tên <span class="text-red-500">*</span></label>
                            <input id="name" name="name" value="{{ $form['name'] }}" required maxlength="255" class="form-input">
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="headline" class="form-label">Vị trí ứng tuyển</label>
                            <input id="headline" name="headline" value="{{ $form['headline'] }}" maxlength="150" placeholder="VD: Nhân viên kinh doanh" class="form-input">
                            @error('headline') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Địa chỉ email</label>
                            <input value="{{ $email }}" disabled class="form-input bg-slate-50 text-slate-500">
                            <p class="mt-1 text-xs text-slate-500">Email tài khoản, hiển thị trên CV.</p>
                        </div>
                        <div>
                            <label for="phone" class="form-label">Số điện thoại</label>
                            <input id="phone" name="phone" value="{{ $form['phone'] }}" maxlength="20" placeholder="0912 345 678" class="form-input">
                            @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="address" class="form-label">Địa chỉ</label>
                            <input id="address" name="address" value="{{ $form['address'] }}" maxlength="255" placeholder="VD: Cầu Giấy, Hà Nội" class="form-input">
                            @error('address') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="date_of_birth" class="form-label">Ngày sinh</label>
                            <input id="date_of_birth" type="date" name="date_of_birth" value="{{ $form['date_of_birth'] }}" class="form-input">
                            @error('date_of_birth') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- 2. Summary --}}
                <section data-step="summary" class="space-y-4" hidden>
                    <h2 class="text-lg font-semibold text-slate-900">Tóm tắt</h2>
                    <p class="text-sm text-slate-500">2–4 câu về kinh nghiệm, thế mạnh và mục tiêu. Nên có con số cụ thể (VD: "tăng doanh số 30%").</p>
                    <div>
                        <textarea id="objective" name="objective" rows="7" maxlength="1000" class="form-input" placeholder="Nhân viên kinh doanh với 3 năm kinh nghiệm B2B...">{{ old('objective', $cv->objective) }}</textarea>
                        <p class="mt-1 text-right text-xs text-slate-400"><span data-count="objective">0</span>/1000</p>
                        @error('objective') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </section>

                {{-- 3. Experience --}}
                <section data-step="experience" class="space-y-4" hidden>
                    <h2 class="text-lg font-semibold text-slate-900">Kinh nghiệm làm việc</h2>
                    <div class="space-y-4" data-list="experiences">
                        @foreach ($form['experiences'] as $i => $row)
                            @include('candidate.cvs.partials.experience-row', ['i' => $i, 'row' => $row])
                        @endforeach
                    </div>
                    <button type="button" data-add="experiences" class="w-full rounded-xl border-2 border-dashed border-slate-300 py-3 text-sm font-semibold text-slate-600 hover:border-blue-400 hover:text-blue-700">+ Thêm kinh nghiệm</button>
                </section>

                {{-- 4. Education --}}
                <section data-step="education" class="space-y-4" hidden>
                    <h2 class="text-lg font-semibold text-slate-900">Học vấn</h2>
                    <div class="space-y-4" data-list="educations">
                        @foreach ($form['educations'] as $i => $row)
                            @include('candidate.cvs.partials.education-row', ['i' => $i, 'row' => $row])
                        @endforeach
                    </div>
                    <button type="button" data-add="educations" class="w-full rounded-xl border-2 border-dashed border-slate-300 py-3 text-sm font-semibold text-slate-600 hover:border-blue-400 hover:text-blue-700">+ Thêm học vấn</button>
                </section>

                {{-- 5. Skills --}}
                <section data-step="skills" class="space-y-4" hidden>
                    <h2 class="text-lg font-semibold text-slate-900">Kỹ năng</h2>
                    <p class="text-sm text-slate-500">Gõ kỹ năng rồi nhấn Enter. Nên có 5–10 kỹ năng khớp với tin tuyển dụng.</p>
                    <div class="flex flex-wrap gap-2 rounded-lg border border-slate-300 p-2 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/15" data-skills>
                        @foreach ($form['skills'] as $skill)
                            <span class="flex items-center gap-1 rounded-full bg-blue-50 py-1 pr-1 pl-3 text-sm text-blue-800" data-skill>
                                {{ $skill }}<input type="hidden" name="skills[]" value="{{ $skill }}">
                                <button type="button" class="grid size-5 place-items-center rounded-full hover:bg-blue-100" data-remove-skill aria-label="Xoá">&times;</button>
                            </span>
                        @endforeach
                        <input type="text" class="min-w-40 flex-1 border-0 px-1 py-1 text-sm focus:outline-none" placeholder="VD: Excel, Đàm phán, Tiếng Anh..." data-skill-input maxlength="50">
                    </div>
                    @error('skills') <p class="form-error">{{ $message }}</p> @enderror
                    @error('skills.*') <p class="form-error">{{ $message }}</p> @enderror
                </section>

                {{-- 6. Finish --}}
                <section data-step="finish" class="space-y-5" hidden>
                    <h2 class="text-lg font-semibold text-slate-900">Hoàn tất</h2>
                    <div>
                        <label for="title" class="form-label">Tên CV <span class="text-red-500">*</span></label>
                        <input id="title" name="title" value="{{ old('title', $cv->title) }}" required maxlength="100" class="form-input">
                        <p class="mt-1 text-xs text-slate-500">Chỉ bạn nhìn thấy, để phân biệt khi có nhiều CV.</p>
                        @error('title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <p class="form-label">Mẫu CV</p>
                        <button type="button" data-open-picker class="flex w-full items-center justify-between rounded-lg border border-slate-300 px-4 py-3 text-left text-sm hover:border-blue-400">
                            <span><b data-template-label>{{ $templates[$currentTemplate]['name'] ?? '' }}</b></span>
                            <span class="font-semibold text-blue-700">Đổi mẫu</span>
                        </button>
                        @error('template') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <p class="form-label">Màu chủ đạo</p>
                        <div class="flex gap-3">
                            @foreach (config('recruitment.cv.accent_colors') as $color)
                                <label class="cursor-pointer">
                                    <input type="radio" name="accent_color" value="{{ $color }}" class="peer sr-only" @checked(old('accent_color', $cv->accent_color) === $color)>
                                    <span class="block size-9 rounded-full ring-2 ring-transparent ring-offset-2 transition peer-checked:ring-slate-900" style="background-color: {{ $color }}"></span>
                                </label>
                            @endforeach
                        </div>
                        @error('accent_color') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-xl bg-blue-50 p-4 text-sm text-blue-800">
                        Thông tin cá nhân, kinh nghiệm, học vấn và kỹ năng được lưu vào <b>hồ sơ</b> của bạn và dùng chung cho mọi CV. Tên, mẫu, màu và phần tóm tắt là riêng của CV này.
                    </div>
                </section>

                <div class="mt-8 flex items-center justify-between border-t border-slate-100 pt-5">
                    <button type="button" class="btn-secondary" data-prev>&larr; Trước</button>
                    <button type="button" class="btn-primary" data-next>Tiếp &rarr;</button>
                    <button type="submit" class="btn-primary" data-submit hidden>{{ $cv->exists ? 'Lưu CV' : 'Lưu & tạo CV' }}</button>
                </div>
            </div>

            {{-- ================= PREVIEW + ATS ================= --}}
            <div class="space-y-5 xl:sticky xl:top-20">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                    <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-2 text-xs text-slate-500">
                        <span>Xem trước trực tiếp</span>
                        <span data-preview-status></span>
                    </div>
                    <div class="max-h-[70vh] overflow-auto p-4">
                        <div class="mx-auto w-[210mm] bg-white shadow-md [zoom:0.62]" data-preview>
                            <p class="p-10 text-center text-slate-400">Đang tải bản xem trước…</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex items-center gap-4">
                        <div class="relative grid size-16 shrink-0 place-items-center rounded-full" data-ats-ring>
                            <span class="grid size-12 place-items-center rounded-full bg-white text-lg font-bold text-slate-900" data-ats-score>0</span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">Điểm ATS <span class="text-sm font-normal text-slate-500">/100</span></p>
                            <p class="text-sm text-slate-500" data-ats-label></p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs font-semibold tracking-wider text-red-600 uppercase">Cần bổ sung</p>
                    <ul class="mt-2 space-y-1.5 text-sm" data-ats-todo></ul>
                    <details class="mt-3">
                        <summary class="cursor-pointer text-xs font-semibold tracking-wider text-emerald-600 uppercase">Đã đạt (<span data-ats-done-count>0</span>)</summary>
                        <ul class="mt-2 space-y-1.5 text-sm text-slate-500" data-ats-done></ul>
                    </details>
                    <p class="mt-4 text-xs text-slate-400">Điểm ước tính theo các tiêu chí phổ biến của hệ thống lọc CV.</p>
                </div>
            </div>
        </div>

        {{-- Row templates for "Thêm" buttons --}}
        <template data-row-template="experiences">@include('candidate.cvs.partials.experience-row', ['i' => '__i__', 'row' => []])</template>
        <template data-row-template="educations">@include('candidate.cvs.partials.education-row', ['i' => '__i__', 'row' => []])</template>
    </form>

    {{-- Template picker --}}
    <dialog id="template-picker" class="m-auto w-[min(94vw,720px)] rounded-2xl p-0 backdrop:bg-slate-900/50">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Chọn mẫu CV</h2>
            <button type="button" class="rounded-lg p-1 text-slate-500 hover:bg-slate-100" data-close-picker aria-label="Đóng">&times;</button>
        </div>
        <div class="grid max-h-[70vh] gap-3 overflow-y-auto p-6 sm:grid-cols-2">
            @foreach ($templates as $key => $template)
                <button type="button" data-pick-template="{{ $key }}" data-name="{{ $template['name'] }}" data-ats="{{ $template['ats'] ? 1 : 0 }}"
                        class="rounded-xl border-2 border-slate-200 p-4 text-left hover:border-blue-400 data-[selected]:border-blue-600 data-[selected]:bg-blue-50">
                    <span class="flex items-center gap-2">
                        <span class="font-semibold text-slate-900">{{ $template['name'] }}</span>
                        @if ($loop->first) <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Dùng nhiều nhất</span> @endif
                        @if ($template['ats']) <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">ATS</span> @endif
                    </span>
                    <span class="mt-1 block text-sm text-slate-500">{{ $template['description'] }}</span>
                    <span class="mt-2 flex flex-wrap gap-1.5 text-xs text-slate-600">
                        <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $template['language'] ?? 'Tiếng Việt' }}</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ ! empty($template['photo']) ? 'Có ô ảnh' : 'Không cần ảnh' }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </dialog>

    @include('candidate.cvs.partials.builder-script')
@endsection
