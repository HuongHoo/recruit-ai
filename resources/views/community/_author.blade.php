{{-- Author name + role badge + time. Expects $user (nullable) and $time (Carbon). --}}
@php
    $badge = match ($user?->role) {
        \App\Enums\UserRole::Employer => 'bg-amber-50 text-amber-700 ring-amber-200',
        \App\Enums\UserRole::Admin => 'bg-rose-50 text-rose-700 ring-rose-200',
        default => 'bg-blue-50 text-blue-700 ring-blue-200',
    };
    $words = preg_split('/\s+/', trim($user?->name ?? '?'));
    $initials = mb_strtoupper(mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
@endphp
<div class="flex min-w-0 items-center gap-2.5 text-sm">
    <span @class(['grid shrink-0 place-items-center rounded-full bg-slate-200 font-bold text-slate-600', $avatarClass ?? 'size-9 text-xs'])>{{ $initials }}</span>
    <div class="min-w-0">
        <p class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
            <span class="truncate font-semibold text-slate-900">{{ $user?->name ?? 'Người dùng đã xoá' }}</span>
            @if ($user)
                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $badge }}">{{ $user->role->label() }}</span>
            @endif
        </p>
        <p class="text-xs text-slate-400"><time datetime="{{ $time->toIso8601String() }}" title="{{ $time->format('H:i d/m/Y') }}">{{ $time->locale('vi')->diffForHumans() }}</time></p>
    </div>
</div>
