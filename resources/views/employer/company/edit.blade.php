@extends('layouts.app')
@section('title', 'Quản lý thông tin công ty')
@section('subtitle', 'Quản lý hồ sơ công ty, logo, điều khoản sử dụng và thông tin liên hệ.')
@section('icon', 'cog')

@php
    /** @var \App\Models\Company $company */
    $card = 'rounded-2xl border border-slate-200 bg-white';
    $cardHead = 'flex items-center gap-3 border-b border-slate-100 px-4 py-3.5';
    $headIcon = 'grid size-9 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-600';
    $maxMb = config('recruitment.employer.logo_max_kb') / 1024;
@endphp

@section('content')
    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-2 text-[13px] text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('employer.dashboard') }}" class="hover:text-slate-800" aria-label="Bảng điều khiển">
            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3 2 12h3v8h5v-6h4v6h5v-8h3L12 3Z"/></svg>
        </a>
        <span class="text-slate-300">&rsaquo;</span>
        <a href="{{ route('employer.settings') }}" class="hover:text-slate-800">Cài đặt công ty</a>
        <span class="text-slate-300">&rsaquo;</span>
        <span class="font-semibold text-slate-800">Quản lý thông tin công ty</span>
    </nav>

    @include('employer.company._status-banner', ['company' => $company->exists ? $company : null])

    <form method="POST" action="{{ route('employer.company.update') }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-[minmax(280px,1fr)_2fr]">
        @csrf
        @method('PUT')

        {{-- ===== Left column ===== --}}
        <div class="space-y-4">
            <section class="{{ $card }}">
                <div class="{{ $cardHead }}">
                    <span class="{{ $headIcon }}"><svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m3 16 5-5 4 4 3-3 6 6M15 9h.01"/></svg></span>
                    <div>
                        <h2 class="font-semibold text-slate-900">Logo công ty @unless ($company->logo_path)<span class="text-red-500">*</span>@endunless</h2>
                        <p class="text-xs text-slate-500">Khuyến nghị: Hình vuông, tối đa {{ $maxMb }}MB. Định dạng: JPG, PNG, WEBP</p>
                    </div>
                </div>
                <div class="flex flex-col items-center px-4 py-6">
                    <label for="logo" class="relative cursor-pointer">
                        <span class="grid size-28 place-items-center overflow-hidden rounded-full border border-slate-200 bg-slate-50 shadow-sm hover:ring-4 hover:ring-teal-100">
                            <img id="logo-preview" src="{{ $company->logo_path ? $company->logoUrl() : '' }}" alt="" @class(['size-full object-cover', 'hidden' => ! $company->logo_path])>
                            @unless ($company->logo_path)
                                <span id="logo-placeholder" class="text-xs text-slate-400">Chưa có logo</span>
                            @endunless
                        </span>
                        <span class="absolute right-0 bottom-1 grid size-8 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h3l2-3h6l2 3h3v11H4V8Zm8 9a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/></svg>
                        </span>
                    </label>
                    <label for="logo" class="mt-3 cursor-pointer text-sm font-semibold text-teal-700 hover:text-teal-800">Chọn tệp</label>
                    <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only" @required(! $company->logo_path)>
                    @error('logo') <p class="form-error text-center">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="{{ $card }}">
                <div class="{{ $cardHead }}">
                    <span class="{{ $headIcon }}"><svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4 6v6c0 5 3.4 8.5 8 9 4.6-.5 8-4 8-9V6l-8-3Zm-3 9 2 2 4-4"/></svg></span>
                    <h2 class="font-semibold text-slate-900">Xác thực doanh nghiệp</h2>
                </div>
                <div class="space-y-4 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="text-slate-600">Trạng thái</span>
                        @if ($company->exists)
                            <span class="{{ $company->status->badgeClasses() }} rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $company->status->label() }}@if ($company->isVerified()) · {{ $company->verified_at?->format('d/m/Y') }}@endif</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Chưa gửi hồ sơ</span>
                        @endif
                    </div>

                    <fieldset>
                        <legend class="mb-2 text-sm text-slate-700">Mô hình hoạt động <span class="text-red-500">*</span></legend>
                        <div class="space-y-2">
                            @foreach ($businessModels as $value => $model)
                                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-3 hover:border-slate-300 has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/60">
                                    <input type="radio" name="business_model" value="{{ $value }}" required class="mt-0.5 size-4 accent-teal-700" @checked(old('business_model', $company->business_model) === $value)>
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900">{{ $model['name'] }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $model['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('business_model') <p class="form-error">{{ $message }}</p> @enderror
                    </fieldset>

                    @if ($company->isVerified())
                        <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">Thay đổi <strong>tên công ty</strong> hoặc <strong>website</strong> sẽ cần Quản trị viên xác thực lại.</p>
                    @endif
                </div>
            </section>
        </div>

        {{-- ===== Right column ===== --}}
        <div class="space-y-4">
            <section class="{{ $card }}">
                <div class="{{ $cardHead }}">
                    <span class="{{ $headIcon }}"><svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 21V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v17m-12 0h16m-4 0V9h3a1 1 0 0 1 1 1v11M8 7h4M8 11h4M8 15h4"/></svg></span>
                    <h2 class="font-semibold text-slate-900">Thông tin công ty</h2>
                </div>
                <div class="grid gap-4 p-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="form-label">Tên công ty <span class="text-red-500">*</span></label>
                        <input id="name" name="name" value="{{ old('name', $company->name) }}" required class="form-input">
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="size" class="form-label">Quy mô công ty</label>
                        <select id="size" name="size" class="form-input">
                            <option value="">Chọn quy mô</option>
                            @foreach ($companySizes as $value => $label)
                                <option value="{{ $value }}" @selected(old('size', $company->size) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('size') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="industry" class="form-label">Lĩnh vực</label>
                        <input id="industry" name="industry" value="{{ old('industry', $company->industry) }}" list="industry-options" class="form-input" placeholder="VD: Công nghệ thông tin">
                        <datalist id="industry-options">
                            @foreach ($industries as $industry) <option value="{{ $industry }}"></option> @endforeach
                        </datalist>
                        @error('industry') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="description" class="form-label">Giới thiệu công ty</label>
                        <textarea id="description" name="description" rows="4" class="form-input" placeholder="Lịch sử, sản phẩm, văn hóa, phúc lợi...">{{ old('description', $company->description) }}</textarea>
                        @error('description') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="{{ $card }}">
                <div class="{{ $cardHead }}">
                    <span class="{{ $headIcon }}"><svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v12H4V6Zm0 0 8 7 8-7"/></svg></span>
                    <h2 class="font-semibold text-slate-900">Thông tin liên hệ</h2>
                </div>
                <div class="grid gap-4 p-4 sm:grid-cols-2">
                    @foreach ([
                        ['contact_email', 'Email liên hệ', 'email', old('contact_email', $company->contact_email ?? auth()->user()->email), 'M4 6h16v12H4V6Zm0 0 8 7 8-7', true, 'hr@congty.vn'],
                        ['phone', 'Số điện thoại liên hệ', 'tel', old('phone', $company->phone), 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z', true, '0901 234 567'],
                        ['website', 'Website', 'url', old('website', $company->website), 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1m-2 7.4a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1', true, 'https://example.com'],
                        ['address', 'Địa chỉ', 'text', old('address', $company->address), 'M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Zm0-8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z', false, 'Số nhà, đường, quận/huyện, tỉnh/thành'],
                    ] as [$field, $label, $type, $value, $icon, $required, $placeholder])
                        <div @class(['sm:col-span-2' => in_array($field, ['website', 'address'])])>
                            <label for="{{ $field }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                <input id="{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ $value }}" @required($required) placeholder="{{ $placeholder }}" class="form-input pl-9">
                            </div>
                            @error($field) <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 backdrop-blur">
                <a href="{{ route('employer.settings') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
                <button type="submit" class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">
                    {{ $company->status === \App\Enums\CompanyStatus::Rejected ? 'Lưu và gửi duyệt lại' : 'Lưu thay đổi' }}
                </button>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('logo').addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (!file) return;
            const preview = document.getElementById('logo-preview');
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            document.getElementById('logo-placeholder')?.remove();
        });
    </script>
@endsection
