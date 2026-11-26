{{-- Priority meter + benefit list. Expects $level (1-4), $rank (string), $has (feature keys). --}}
<div class="mt-5 border-t border-slate-100 pt-4">
    <div class="flex items-center justify-between text-xs text-slate-500">
        <span>Mức ưu tiên</span>
        <span class="flex gap-1">
            @for ($i = 1; $i <= 4; $i++)
                <span @class(['h-1.5 w-4 rounded-full', 'bg-teal-600' => $i <= $level, 'bg-slate-200' => $i > $level])></span>
            @endfor
        </span>
    </div>
    <p class="mt-3 border-b border-slate-100 pb-3 text-sm font-semibold text-slate-900">{{ $rank }}</p>
    <ul class="mt-3 space-y-2.5 text-[13px]">
        @foreach (config('recruitment.package_features') as $key => $label)
            @php $on = in_array($key, $has, true); @endphp
            <li @class(['flex items-start gap-2', 'text-slate-700' => $on, 'text-slate-400' => ! $on])>
                @if ($on)
                    <span class="mt-0.5 grid size-4 shrink-0 place-items-center rounded-full bg-teal-100 text-[10px] text-teal-700">✓</span>
                @else
                    <span class="mt-0.5 grid size-4 shrink-0 place-items-center rounded-full bg-slate-100 text-[10px] text-slate-400">–</span>
                @endif
                {{ $label }}
            </li>
        @endforeach
    </ul>
</div>
