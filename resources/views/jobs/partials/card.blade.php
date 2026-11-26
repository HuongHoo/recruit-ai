{{-- Job card. Expects $job; optional $isSaved (bool) shows the save/unsave toggle for candidates. --}}
@php $promotion = $job->activePromotion(); @endphp
<article @class(['group relative flex flex-col rounded-2xl border p-5 transition hover:shadow-lg hover:shadow-blue-900/5',
    'border-blue-200 bg-gradient-to-br from-blue-50 to-white hover:border-blue-300' => $promotion,
    'border-slate-200 bg-white hover:border-blue-200' => ! $promotion])>
    @if ($promotion && in_array('badge', $promotion['features'], true))
        <span class="absolute -top-2.5 right-4 rounded-full bg-gradient-to-r from-orange-500 to-pink-500 px-2.5 py-0.5 text-[11px] font-bold text-white shadow">🔥 {{ $promotion['name'] }}</span>
    @endif
    <div class="flex items-start gap-4">
        <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-blue-600 text-lg font-bold text-white">
            {{ mb_strtoupper(mb_substr($job->company_name, 0, 1)) }}
        </span>
        <div class="min-w-0 flex-1">
            <h3 class="font-semibold text-slate-900 group-hover:text-blue-700">
                <a href="{{ route('jobs.show', $job) }}" class="after:absolute after:inset-0">{{ $job->title }}</a>
            </h3>
            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $job->company_name }}</p>
        </div>

        @isset($isSaved)
            <form method="POST" action="{{ $isSaved ? route('candidate.saved-jobs.destroy', $job) : route('candidate.saved-jobs.store', $job) }}" class="relative z-10">
                @csrf
                @if ($isSaved) @method('DELETE') @endif
                <button type="submit" @class([
                    'rounded-full p-1.5 hover:bg-rose-50',
                    'text-rose-500' => $isSaved,
                    'text-slate-400 hover:text-rose-500' => ! $isSaved,
                ]) aria-label="{{ $isSaved ? 'Bỏ lưu' : 'Lưu việc làm' }}" title="{{ $isSaved ? 'Bỏ lưu' : 'Lưu việc làm' }}">
                    <svg class="size-5" fill="{{ $isSaved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.4-9.3-9A5.2 5.2 0 0 1 12 6a5.2 5.2 0 0 1 9.3 6c-2.3 4.6-9.3 9-9.3 9Z"/></svg>
                </button>
            </form>
        @endisset
    </div>

    <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium">
        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">{{ $job->salaryLabel() }}</span>
        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ $job->location }}</span>
        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ $job->job_type->label() }}</span>
        @if ($job->experience)
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ $job->experience }}</span>
        @endif
    </div>

    <p class="mt-4 text-xs text-slate-400">
        Đăng {{ $job->created_at->diffForHumans() }}
        @if ($job->deadline) · Hạn nộp {{ $job->deadline->format('d/m/Y') }} @endif
    </p>
</article>
