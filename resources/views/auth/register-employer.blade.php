@extends('layouts.auth')
@section('title', 'Đăng ký nhà tuyển dụng')
@section('container_width', 'max-w-2xl')

@php
    $required = '<span class="text-red-500">*</span>';
    $eyeIcon = '<svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3 9.96 7.18.07.2.07.43 0 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3-9.96-7.18Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>';
@endphp

@section('content')
    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">Nhà tuyển dụng</span>
    <h1 class="mt-3 text-2xl font-bold text-slate-900 sm:text-3xl">Tạo tài khoản của bạn</h1>
    <p class="mt-2 text-sm text-slate-500">
        Đã có tài khoản?
        <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:text-emerald-800">Đăng nhập tại đây</a>
        · Bạn là ứng viên? <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-700">Đăng ký ứng viên</a>
    </p>

    <form method="POST" action="{{ route('register.employer') }}" enctype="multipart/form-data" class="mt-8 space-y-5">
        @csrf

        {{-- Account --}}
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="form-label">Tên {!! $required !!}</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name"
                       placeholder="Nguyễn Văn A" class="form-input">
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="form-label">Số điện thoại {!! $required !!}</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel"
                       placeholder="0901 234 567" class="form-input">
                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="email" class="form-label">Email đăng nhập {!! $required !!}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="hr@congty.com" class="form-input">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            @foreach (['password' => ['Mật khẩu', '8 ký tự trở lên'], 'password_confirmation' => ['Xác nhận mật khẩu', 'Nhập lại mật khẩu']] as $field => [$label, $placeholder])
                <div>
                    <label for="{{ $field }}" class="form-label">{{ $label }} {!! $required !!}</label>
                    <div class="relative">
                        <input id="{{ $field }}" type="password" name="{{ $field }}" required autocomplete="new-password"
                               placeholder="{{ $placeholder }}" class="form-input pr-11">
                        <button type="button" data-toggle-password="{{ $field }}" aria-label="Hiện / ẩn mật khẩu"
                                class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-400 hover:text-slate-600">
                            {!! $eyeIcon !!}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        @error('password') <p class="form-error -mt-3">{{ $message }}</p> @enderror

        {{-- Company --}}
        <div class="flex items-center gap-4 pt-4">
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="text-sm text-slate-500">Thông tin công ty</span>
            <span class="h-px flex-1 bg-slate-200"></span>
        </div>

        <div class="flex flex-col items-center">
            <label for="logo" class="group relative grid size-24 cursor-pointer place-items-center rounded-full border-2 border-dashed border-slate-300 bg-slate-50 transition hover:border-emerald-500">
                <img id="logo-preview" alt="" class="hidden size-full rounded-full object-cover">
                <svg id="logo-placeholder" class="size-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.91 2.91M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5v-15A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Zm10.5-11.25h.01v.01h-.01v-.01Z"/></svg>
                <span class="absolute right-0 bottom-0 grid size-7 place-items-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.83 6.18A2.31 2.31 0 0 1 5.18 7.23c-.38.05-.76.11-1.13.18C2.99 7.58 2.25 8.5 2.25 9.57V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.57c0-1.07-.75-1.99-1.8-2.16a47.9 47.9 0 0 0-1.13-.18 2.31 2.31 0 0 1-1.64-1.05l-.82-1.32a2.19 2.19 0 0 0-1.74-1.04 48.8 48.8 0 0 0-5.23 0 2.19 2.19 0 0 0-1.74 1.04l-.82 1.32Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                </span>
            </label>
            <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="sr-only">
            <label for="logo" class="mt-2 cursor-pointer text-sm font-semibold text-emerald-700">Logo công ty {!! $required !!}</label>
            <p class="text-xs text-slate-400">PNG, JPG, WEBP — tối đa {{ config('recruitment.employer.logo_max_kb') / 1024 }}MB</p>
            @error('logo') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <fieldset>
            <legend class="form-label mb-0">Mô hình hoạt động của công ty {!! $required !!}</legend>
            <p class="mb-3 text-xs text-slate-500">Chọn đúng mô hình để hệ thống tự động thiết lập các tính năng phù hợp.</p>
            <div class="space-y-3">
                @foreach ($businessModels as $value => $model)
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-slate-300 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/60">
                        <input type="radio" name="business_model" value="{{ $value }}" required
                               class="mt-1 size-4 accent-emerald-700" @checked(old('business_model') === $value)>
                        <span>
                            <span class="block font-semibold text-slate-900">{{ $model['name'] }}</span>
                            <span class="mt-0.5 block text-sm text-slate-500">{{ $model['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('business_model') <p class="form-error">{{ $message }}</p> @enderror
        </fieldset>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="company_name" class="form-label">Tên công ty {!! $required !!}</label>
                <input id="company_name" type="text" name="company_name" value="{{ old('company_name') }}" required
                       autocomplete="organization" placeholder="Công ty TNHH Mẫu" class="form-input">
                @error('company_name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="company_size" class="form-label">Quy mô công ty</label>
                <select id="company_size" name="company_size" class="form-input">
                    <option value="">Chọn quy mô công ty (tùy chọn)</option>
                    @foreach ($companySizes as $value => $label)
                        <option value="{{ $value }}" @selected(old('company_size') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('company_size') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="industry" class="form-label">Ngành nghề</label>
            <input id="industry" type="text" name="industry" value="{{ old('industry') }}" list="industry-options"
                   placeholder="CNTT · Phần mềm (tùy chọn)" class="form-input">
            <datalist id="industry-options">
                @foreach ($industries as $industry)
                    <option value="{{ $industry }}"></option>
                @endforeach
            </datalist>
            @error('industry') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="website" class="form-label">Website {!! $required !!}</label>
            <input id="website" type="url" name="website" value="{{ old('website') }}" required autocomplete="url"
                   placeholder="https://example.com" class="form-input">
            @error('website') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="form-label">Hồ sơ công ty</label>
            <textarea id="description" name="description" rows="4" class="form-input"
                      placeholder="Nhập tổng quan công ty hoặc mô tả hoạt động kinh doanh (tùy chọn)">{{ old('description') }}</textarea>
            @error('description') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="flex items-start gap-2 text-sm text-slate-600">
                <input type="checkbox" name="terms" value="1" required class="mt-0.5 size-4 accent-emerald-700" @checked(old('terms'))>
                <span>Tôi đồng ý với <span class="font-semibold text-emerald-700 underline">Điều khoản sử dụng</span> và <span class="font-semibold text-emerald-700 underline">Chính sách bảo mật</span> {!! $required !!}</span>
            </label>
            @error('terms') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-3 font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-500/30">
            Tạo tài khoản
        </button>
    </form>

    <script>
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.togglePassword);
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });

        document.getElementById('logo').addEventListener('change', (event) => {
            const file = event.target.files[0];
            const preview = document.getElementById('logo-preview');
            if (! file) {
                return;
            }
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            document.getElementById('logo-placeholder').classList.add('hidden');
        });
    </script>
@endsection
