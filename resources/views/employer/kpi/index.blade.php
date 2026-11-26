@extends('layouts.app')
@section('title', 'Theo dõi tiến độ công việc')
@section('subtitle', $from->format('d/m/Y').' – '.$to->format('d/m/Y'))
@section('icon', 'target')

@php
    $seg = 'rounded-lg border px-3 py-2 text-sm font-medium';
@endphp

@section('content')
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        {{-- Toolbar --}}
        <form method="GET" class="flex flex-wrap items-center justify-end gap-2 border-b border-slate-200 bg-slate-50/60 p-3">
            <select name="period" class="form-input w-28 py-2" onchange="this.form.querySelectorAll('[data-period-input]').forEach((i) => i.disabled = true); this.form.submit()" aria-label="Chu kỳ">
                <option value="week" @selected($period === 'week')>Tuần</option>
                <option value="month" @selected($period === 'month')>Tháng</option>
            </select>
            <a href="{{ route('employer.kpi', $prev) }}" class="grid size-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" aria-label="Kỳ trước">&lsaquo;</a>
            @if ($period === 'week')
                <input type="week" name="week" data-period-input value="{{ $from->format('o-\WW') }}" onchange="this.form.submit()" class="form-input w-44 py-2" aria-label="Chọn tuần">
            @else
                <input type="month" name="month" data-period-input value="{{ $from->format('Y-m') }}" onchange="this.form.submit()" class="form-input w-44 py-2" aria-label="Chọn tháng">
            @endif
            <a href="{{ route('employer.kpi', $next) }}" class="grid size-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" aria-label="Kỳ sau">&rsaquo;</a>
            <a href="{{ route('employer.kpi', ['period' => $period]) }}" @class([$seg, 'inline-flex items-center gap-1.5',
                'border-teal-600 bg-white text-teal-700 hover:bg-teal-50' => ! $isCurrent,
                'border-teal-600 bg-teal-600 text-white' => $isCurrent])>
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg>
                {{ $period === 'week' ? 'Tuần này' : 'Tháng này' }}
            </a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm" data-kpi-table>
                <thead class="border-b border-slate-200 text-[13px] text-slate-600">
                    <tr class="divide-x divide-slate-200">
                        <th class="w-72 px-4 py-3 font-semibold">
                            <button type="button" data-sort="name" class="flex w-full items-center justify-between">
                                <span class="text-left">Nhân viên<span class="block text-[11px] font-normal text-slate-400">Bấm để sắp xếp</span></span>
                                <span class="text-slate-300">⇅</span>
                            </button>
                        </th>
                        @foreach ($metrics as $key => [$label, $dot])
                            <th class="px-4 py-3 font-semibold">
                                <button type="button" data-sort="{{ $key }}" class="flex w-full items-center justify-between gap-2">
                                    <span class="flex items-center gap-1.5"><span class="{{ $dot }} size-1.5 rounded-full"></span>{{ $label }}</span>
                                    <span class="text-slate-300">⇅</span>
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                @foreach ($rows as $index => $row)
                    @php
                        $member = $row['member'];
                        $words = preg_split('/\s+/', trim($member->name));
                        $initials = mb_strtoupper(mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
                    @endphp
                    <tbody data-row data-name="{{ $member->name }}" @foreach ($row['totals'] as $key => $value) data-{{ $key }}="{{ $value }}" @endforeach class="border-b border-slate-100">
                        <tr class="divide-x divide-slate-100 hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <button type="button" data-toggle class="flex w-full items-center gap-3 text-left" aria-expanded="false">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-pink-500 text-[11px] font-bold text-white">{{ $initials }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-semibold text-slate-900">{{ $member->name }}</span>
                                        <span class="block truncate text-xs text-slate-500">{{ $member->email }}</span>
                                    </span>
                                    <svg data-chevron class="size-4 shrink-0 text-slate-400 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                </button>
                            </td>
                            @foreach ($row['totals'] as $value)
                                <td @class(['px-4 py-3', 'font-semibold text-slate-900' => $value > 0, 'text-slate-400' => $value === 0])>{{ $value }}</td>
                            @endforeach
                        </tr>
                        @foreach ($row['breakdown'] as $bucket)
                            <tr data-detail class="hidden divide-x divide-slate-100 bg-slate-50/60 text-[13px]">
                                <td class="py-2 pr-4 pl-15 text-slate-500">{{ $bucket['label'] }}</td>
                                @foreach ($bucket['values'] as $value)
                                    <td @class(['px-4 py-2', 'font-medium text-slate-700' => $value > 0, 'text-slate-300' => $value === 0])>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach

                <tfoot class="border-t-2 border-slate-200 bg-slate-100/70 font-semibold text-slate-900">
                    <tr class="divide-x divide-slate-200">
                        <td class="px-4 py-3">Tổng đội</td>
                        @foreach ($team as $value)
                            <td class="px-4 py-3">{{ $value }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
            <span class="font-semibold text-slate-600">Cách tính:</span>
            Tin đã đăng và Nhận hồ sơ theo ngày tạo · Nhận phỏng vấn theo ngày phỏng vấn (không tính lịch đã hủy) ·
            Đã tuyển / Bị loại theo ngày hồ sơ được chuyển sang trạng thái Đạt / Không đạt.
        </p>
    </section>

    <script>
        (() => {
            const table = document.querySelector('[data-kpi-table]');
            table.querySelectorAll('[data-toggle]').forEach((btn) => btn.addEventListener('click', () => {
                const body = btn.closest('[data-row]');
                const open = btn.getAttribute('aria-expanded') !== 'true';
                btn.setAttribute('aria-expanded', open);
                btn.querySelector('[data-chevron]').classList.toggle('rotate-180', open);
                body.querySelectorAll('[data-detail]').forEach((tr) => tr.classList.toggle('hidden', !open));
            }));

            let current = { key: null, dir: 1 };
            table.querySelectorAll('[data-sort]').forEach((btn) => btn.addEventListener('click', () => {
                const key = btn.dataset.sort;
                current = { key, dir: current.key === key ? -current.dir : (key === 'name' ? 1 : -1) };
                const bodies = [...table.querySelectorAll('[data-row]')];
                bodies.sort((a, b) => key === 'name'
                    ? current.dir * a.dataset.name.localeCompare(b.dataset.name, 'vi')
                    : current.dir * (Number(a.dataset[key]) - Number(b.dataset[key])));
                bodies.forEach((b) => table.querySelector('tfoot').before(b));
            }));
        })();
    </script>
@endsection
