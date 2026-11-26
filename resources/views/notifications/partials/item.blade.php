{{-- One notification row: links through notifications.show, which marks it read then redirects. --}}
@php $isUnread = $notification->read_at === null; @endphp
<a href="{{ route('notifications.show', $notification->id) }}" @class(['flex gap-3 px-4 py-3 transition hover:bg-slate-50', 'bg-blue-50/60' => $isUnread])>
    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-blue-100 text-blue-600">
        @if (($notification->data['icon'] ?? null) === 'calendar')
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg>
        @elseif (($notification->data['icon'] ?? null) === 'chat')
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6 3-3h11a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14Z"/></svg>
        @else
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>
        @endif
    </span>
    <span class="min-w-0 flex-1">
        <span class="block text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Thông báo' }}</span>
        <span @class(['block text-sm text-slate-600', 'line-clamp-2' => $compact ?? false])>{{ $notification->data['message'] ?? '' }}</span>
        <span class="mt-1 block text-xs text-slate-400">{{ $notification->created_at->locale('vi')->diffForHumans() }}</span>
    </span>
    @if ($isUnread)
        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-blue-600" aria-label="Chưa đọc"></span>
    @endif
</a>
