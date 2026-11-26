@extends('layouts.auth')
@section('title', 'Đăng nhập')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Chào mừng trở lại 👋</h1>
    <p class="mt-2 text-sm text-slate-500">Đăng nhập để tiếp tục với tài khoản của bạn.</p>

    @if (session('status'))
        <div class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="mt-8">
        @include('auth._google-button', ['label' => 'Đăng nhập với Google'])
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   placeholder="ban@example.com" @class(['form-input', 'border-red-500' => $errors->has('email')])>
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="form-label">Mật khẩu</label>
                <a href="{{ route('password.request') }}" class="mb-1.5 text-sm font-medium text-blue-600 hover:text-blue-700">Quên mật khẩu?</a>
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••" class="form-input">
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-blue-600">
            Ghi nhớ đăng nhập
        </label>

        <button type="submit" class="btn-primary w-full">Đăng nhập</button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        Chưa có tài khoản?
        <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-700">Đăng ký ứng viên</a>
        ·
        <a href="{{ route('register', ['role' => 'employer']) }}" class="font-semibold text-emerald-700 hover:text-emerald-800">Đăng ký nhà tuyển dụng</a>
    </p>
@endsection
