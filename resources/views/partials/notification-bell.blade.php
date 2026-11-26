{{-- Notification bell dropdown for the signed-in user. --}}
@php
    $unreadCount = auth()->user()->unreadNotifications()->count();
    $recentNotifications = auth()->user()->notifications()->limit(5)->get();
@endphp
<details data-dropdown class="group relative">
    <summary class="relative grid size-10 cursor-pointer list-none place-items-center rounded-full text-slate-600 hover:bg-slate-100 group-open:bg-blue-50 group-open:text-blue-700 group-open:ring-2 group-open:ring-blue-600 [&::-webkit-details-marker]:hidden" aria-label="Thông báo">
        <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0m6 0H9"/></svg>
        @if ($unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 grid min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] leading-4 font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </summary>
    <div class="absolute right-0 z-40 mt-2 w-[min(92vw,380px)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="font-semibold text-slate-900">Thông báo</p>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="text-xs font-semibold text-blue-700 hover:text-blue-800">✓✓ Đánh dấu tất cả đã đọc</button>
                </form>
            @endif
        </div>
        <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            @forelse ($recentNotifications as $notification)
                <li>
                    @include('notifications.partials.item', ['notification' => $notification, 'compact' => true])
                </li>
            @empty
                <li class="px-4 py-10 text-center text-sm text-slate-500">Bạn chưa có thông báo nào.</li>
            @endforelse
        </ul>
        <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 py-3 text-center text-sm font-semibold text-blue-700 hover:bg-slate-50">Xem tất cả thông báo &rarr;</a>
    </div>
</details>
