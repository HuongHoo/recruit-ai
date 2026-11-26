<div class="flex shrink-0 items-start gap-1">
    <a href="{{ $editUrl }}" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Sửa" aria-label="Sửa">
        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.9 4.6 2.5 2.5M4 20l1-4L16.2 4.8a1.8 1.8 0 0 1 2.5 0l.5.5a1.8 1.8 0 0 1 0 2.5L8 19l-4 1Z"/></svg>
    </a>
    <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm(@js($confirm))">
        @csrf
        @method('DELETE')
        <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Xóa" aria-label="Xóa">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
        </button>
    </form>
</div>
