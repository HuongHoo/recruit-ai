@extends('layouts.public')
@section('title', 'Không có quyền truy cập')

@section('content')
    <div class="mx-auto flex max-w-lg flex-col items-center px-4 py-24 text-center">
        <span class="grid size-16 place-items-center rounded-full bg-red-100 text-red-600">
            <svg class="size-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
        </span>
        <p class="mt-6 text-sm font-semibold text-red-600">Lỗi 403</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900">Không có quyền truy cập</h1>
        <p class="mt-3 text-slate-600">{{ $exception->getMessage() ?: 'Bạn không có quyền truy cập trang này.' }}</p>
        @auth
            <p class="mt-1 text-sm text-slate-500">Bạn đang đăng nhập với vai trò <strong>{{ auth()->user()->role->label() }}</strong>.</p>
        @endauth
        <div class="mt-8 flex gap-3">
            @auth
                <a href="{{ auth()->user()->homeUrl() }}" class="btn-primary">Về trang của tôi</a>
            @else
                <a href="{{ route('login') }}" class="btn-primary">Đăng nhập</a>
            @endauth
            <a href="{{ route('home') }}" class="btn-secondary">Trang chủ</a>
        </div>
    </div>
@endsection
