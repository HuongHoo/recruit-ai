@extends('layouts.auth')
@section('title', 'Quên mật khẩu')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Quên mật khẩu?</h1>
    <p class="mt-2 text-sm text-slate-500">Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết để bạn đặt lại mật khẩu.</p>

    @if (session('status'))
        <div class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   placeholder="ban@example.com" class="form-input">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Gửi liên kết đặt lại mật khẩu</button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">&larr; Quay lại đăng nhập</a>
    </p>
@endsection
