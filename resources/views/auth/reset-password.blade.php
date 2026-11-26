@extends('layouts.auth')
@section('title', 'Đặt lại mật khẩu')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Đặt lại mật khẩu</h1>
    <p class="mt-2 text-sm text-slate-500">Chọn mật khẩu mới cho tài khoản của bạn.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="form-input">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="form-label">Mật khẩu mới</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                   placeholder="Tối thiểu 8 ký tự" class="form-input">
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="form-label">Nhập lại mật khẩu mới</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-input">
        </div>

        <button type="submit" class="btn-primary w-full">Đặt lại mật khẩu</button>
    </form>
@endsection
