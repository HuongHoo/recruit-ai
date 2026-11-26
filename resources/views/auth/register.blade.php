@extends('layouts.auth')
@section('title', 'Đăng ký')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Tạo tài khoản</h1>
    <p class="mt-2 text-sm text-slate-500">Miễn phí và chỉ mất chưa đầy một phút.</p>

    <div class="mt-8">
        <p class="form-label">Bạn là</p>
        <div class="grid grid-cols-2 gap-3">
            <span class="block rounded-xl border-2 border-blue-600 bg-blue-50 p-4">
                <span class="block font-semibold text-slate-900">Ứng viên</span>
                <span class="mt-0.5 block text-xs text-slate-500">Tìm việc và ứng tuyển</span>
            </span>
            <a href="{{ route('register', ['role' => 'employer']) }}" class="block rounded-xl border-2 border-slate-200 p-4 transition hover:border-slate-300">
                <span class="block font-semibold text-slate-900">Nhà tuyển dụng</span>
                <span class="mt-0.5 block text-xs text-slate-500">Đăng tin và tuyển người</span>
            </a>
        </div>
    </div>

    <div class="mt-5">
        @include('auth._google-button', ['label' => 'Đăng ký nhanh với Google'])
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="form-label">Họ và tên</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name"
                   placeholder="Nguyễn Văn A" class="form-input">
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="ban@example.com" class="form-input">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="form-label">Mật khẩu</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       placeholder="Tối thiểu 8 ký tự" class="form-input">
            </div>
            <div>
                <label for="password_confirmation" class="form-label">Nhập lại mật khẩu</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       autocomplete="new-password" class="form-input">
            </div>
        </div>
        @error('password') <p class="form-error -mt-3">{{ $message }}</p> @enderror

        <button type="submit" class="btn-primary w-full">Tạo tài khoản</button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        Đã có tài khoản?
        <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">Đăng nhập</a>
    </p>
@endsection
