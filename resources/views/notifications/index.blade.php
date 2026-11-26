@extends('layouts.public')
@section('title', 'Thông báo')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Thông báo</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="text-sm font-semibold text-blue-700 hover:text-blue-800">Đánh dấu tất cả đã đọc</button>
                </form>
            @endif
        </div>

        <ul class="mt-6 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @forelse ($notifications as $notification)
                <li>@include('notifications.partials.item', ['notification' => $notification])</li>
            @empty
                <li class="px-6 py-14 text-center text-slate-500">Bạn chưa có thông báo nào.</li>
            @endforelse
        </ul>
        <div class="mt-6">{{ $notifications->links() }}</div>
    </section>
@endsection
