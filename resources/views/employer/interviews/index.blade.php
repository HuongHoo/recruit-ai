@extends('layouts.app')
@section('title', 'Lịch phỏng vấn')
@section('subtitle', 'Xem tổng quan lịch phỏng vấn ứng viên trong 30 ngày gần nhất')
@section('icon', 'calendar')

@php
    $weekdays = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
    $weekdayNames = ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ Nhật'];
    $chip = [
        'scheduled' => 'border-l-teal-500 bg-teal-50 text-teal-900',
        'completed' => 'border-l-slate-400 bg-slate-100 text-slate-500',
        'cancelled' => 'border-l-red-400 bg-red-50 text-red-400 line-through',
    ];
    $query = fn (array $over) => route('employer.interviews.index', array_filter([
        'view' => $view, 'range' => $range, 'interviewer' => $filters['interviewer'] ?? null,
        'from' => $range === 'custom' ? $from->format('Y-m-d') : null,
        'to' => $range === 'custom' ? $to->format('Y-m-d') : null,
        ...$over,
    ]));

    // Data the edit dialog is filled from.
    $payload = $interviews->mapWithKeys(fn ($i) => [$i->id => [
        'id' => $i->id,
        'candidate' => $i->application->candidate->name,
        'email' => $i->application->candidate->email,
        'job' => $i->application->jobPost->title,
        'applicationUrl' => route('employer.applications.show', $i->application),
        'scheduled_at' => $i->scheduled_at->format('Y-m-d\TH:i'),
        'when' => $i->scheduled_at->format('d/m/Y').' · '.$i->timeRange(),
        'duration_minutes' => $i->duration_minutes,
        'type' => $i->type->value,
        'location' => $i->location,
        'interviewer' => $i->interviewer,
        'note' => $i->note,
        'status' => $i->status->value,
        'statusLabel' => $i->status->label(),
        'update' => route('employer.interviews.update', $i),
        'statusUrl' => route('employer.interviews.status', $i),
        'destroy' => route('employer.interviews.destroy', $i),
    ]]);
    $reopen = $errors->any() ? (old('_interview') ? 'edit:'.old('_interview') : 'create') : ($preselect ? 'create' : null);
    $seg = 'rounded-md px-3 py-1.5 text-[13px] font-medium';
    $segOn = 'bg-white text-teal-700 ring-1 ring-teal-600 shadow-sm';
    $segOff = 'text-slate-600 hover:text-slate-900';
@endphp

@section('content')
    {{-- Stats --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Tổng số lịch phỏng vấn', $stats['total']], ['Hôm nay', $stats['today']], ['Tuần này', $stats['week']], ['Tháng này', $stats['month']]] as [$label, $value])
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5">
                <p class="text-[13px] font-medium text-slate-600">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <section class="mt-3 rounded-xl border border-slate-200 bg-white">
        {{-- Toolbar --}}
        <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-slate-200 p-3">
            <input type="hidden" name="view" value="{{ $view }}">
            <input type="hidden" name="range" value="{{ $range }}">
            <label class="relative min-w-52 flex-1 lg:max-w-64">
                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 20V10m6 10V4m6 16v-7m4 7H2"/></svg>
                <select name="interviewer" class="form-input pl-9" onchange="this.form.submit()" aria-label="Lọc theo nhân viên phỏng vấn">
                    <option value="">Tất cả nhân viên</option>
                    @foreach ($interviewers as $name)
                        <option @selected(($filters['interviewer'] ?? '') === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex flex-wrap items-center gap-2 lg:ml-auto">
                <div class="flex rounded-lg bg-slate-100 p-1">
                    <a href="{{ $query(['view' => 'list']) }}" @class([$seg, $view === 'list' ? $segOn : $segOff])>Danh sách</a>
                    <a href="{{ $query(['view' => 'calendar']) }}" @class([$seg, $view === 'calendar' ? $segOn : $segOff])>Lịch</a>
                </div>
                <div class="flex rounded-lg bg-slate-100 p-1">
                    @foreach (['today' => 'Hôm nay', 'week' => 'Tuần này', 'month' => 'Tháng này', 'next30' => '30 ngày tới'] as $key => $label)
                        <a href="{{ $query(['range' => $key, 'from' => null, 'to' => null]) }}" @class([$seg, $range === $key ? $segOn : $segOff])>{{ $label }}</a>
                    @endforeach
                </div>
                <div class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1 text-[13px]">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase">Từ</span>
                    <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" data-date-input class="border-0 p-1 text-[13px] focus:ring-0">
                    <span class="text-slate-300">–</span>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase">Đến</span>
                    <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" data-date-input class="border-0 p-1 text-[13px] focus:ring-0">
                </div>
                <button type="button" data-open-create class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-3 py-2 text-[13px] font-semibold text-white hover:bg-teal-700">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                    Thêm lịch
                </button>
            </div>
            @error('to') <p class="form-error w-full">{{ $message }}</p> @enderror
        </form>

        @if ($view === 'calendar')
            <div class="flex items-center gap-2 px-4 py-3">
                <h2 class="text-lg font-bold text-slate-900">Tháng {{ $month->month }}, {{ $month->year }}</h2>
                <a href="{{ $query(['range' => 'custom', 'from' => $month->copy()->subMonth()->format('Y-m-d'), 'to' => $month->copy()->subMonth()->endOfMonth()->format('Y-m-d')]) }}" class="grid size-8 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" aria-label="Tháng trước">&lsaquo;</a>
                <a href="{{ $query(['range' => 'custom', 'from' => $month->copy()->addMonth()->format('Y-m-d'), 'to' => $month->copy()->addMonth()->endOfMonth()->format('Y-m-d')]) }}" class="grid size-8 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" aria-label="Tháng sau">&rsaquo;</a>
            </div>
            <div class="overflow-x-auto">
                <div class="min-w-[640px]">
                    <div class="grid grid-cols-7 border-y border-slate-200 bg-slate-50 text-center text-xs font-semibold text-slate-500">
                        @foreach ($weekdays as $day) <div class="py-2">{{ $day }}</div> @endforeach
                    </div>
                    <div class="grid grid-cols-7">
                        @for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay())
                            @php $items = $byDay[$day->toDateString()] ?? collect(); $inMonth = $day->month === $month->month; @endphp
                            <div @class(['min-h-[104px] border-r border-b border-slate-100 p-1.5', 'bg-slate-50/60' => ! $inMonth])>
                                <div class="mb-1 flex items-center justify-between">
                                    <span @class(['grid size-6 place-items-center rounded-full text-xs font-semibold', 'bg-teal-600 text-white' => $day->isToday(), 'text-slate-700' => $inMonth && ! $day->isToday(), 'text-slate-300' => ! $inMonth])>{{ $day->day }}</span>
                                    @if ($day->gte(today()))
                                        <button type="button" data-open-create data-date="{{ $day->format('Y-m-d') }}" class="grid size-5 place-items-center rounded text-slate-300 hover:bg-teal-50 hover:text-teal-600" aria-label="Thêm lịch ngày {{ $day->format('d/m') }}">+</button>
                                    @endif
                                </div>
                                <div class="space-y-1">
                                    @foreach ($items->take(3) as $interview)
                                        <button type="button" data-open-edit="{{ $interview->id }}" class="{{ $chip[$interview->status->value] }} block w-full truncate rounded border-l-[3px] px-1.5 py-0.5 text-left text-[11px] hover:brightness-95">
                                            <span class="font-semibold">{{ $interview->scheduled_at->format('H:i') }}</span> {{ $interview->application->candidate->name }}
                                        </button>
                                    @endforeach
                                    @if ($items->count() > 3) <p class="px-1 text-[11px] font-medium text-slate-500">+{{ $items->count() - 3 }} lịch khác</p> @endif
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        @elseif ($interviews->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                    <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg>
                </span>
                <h2 class="mt-4 font-bold text-slate-900">Chưa có lịch phỏng vấn nào</h2>
                <p class="mt-1 max-w-md text-sm text-slate-500">Trong khoảng thời gian đã chọn chưa có lịch phỏng vấn nào được lên lịch. Hãy thử chọn khoảng thời gian khác hoặc thêm lịch phỏng vấn từ trang hồ sơ ứng viên.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($byDay as $date => $items)
                    @php $d = \Illuminate\Support\Carbon::parse($date); @endphp
                    <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row">
                        <div class="flex shrink-0 items-center gap-3 sm:w-40 sm:flex-col sm:items-start sm:gap-0">
                            <p @class(['text-sm font-bold', 'text-teal-700' => $d->isToday(), 'text-slate-900' => ! $d->isToday()])>{{ $d->isToday() ? 'Hôm nay' : $weekdayNames[$d->dayOfWeekIso - 1] }}</p>
                            <p class="text-xs text-slate-500">{{ $d->format('d/m/Y') }} · {{ $items->count() }} lịch</p>
                        </div>
                        <ul class="flex-1 space-y-2">
                            @foreach ($items as $interview)
                                <li>
                                    <button type="button" data-open-edit="{{ $interview->id }}" class="flex w-full flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border border-slate-200 px-3 py-2.5 text-left text-sm hover:border-teal-300 hover:bg-teal-50/30">
                                        <span class="w-24 font-semibold text-slate-900">{{ $interview->timeRange() }}</span>
                                        <span class="min-w-40 flex-1">
                                            <span class="block font-semibold text-slate-900">{{ $interview->application->candidate->name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $interview->application->jobPost->title }}</span>
                                        </span>
                                        <span class="text-xs text-slate-600">{{ $interview->type->label() }}</span>
                                        <span class="w-36 truncate text-xs text-slate-500">{{ $interview->interviewer ? '👤 '.$interview->interviewer : '' }}</span>
                                        <span class="{{ $interview->status->badgeClasses() }} rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $interview->status->label() }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        document.querySelectorAll('[data-date-input]').forEach((input) => input.addEventListener('change', () => {
            input.form.range.value = 'custom';
            input.form.submit();
        }));
    </script>

    {{-- ===== Create / edit dialog ===== --}}
    <dialog id="interview-dialog" class="m-auto w-[min(94vw,560px)] rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/50">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 data-dialog-title class="text-lg font-bold text-slate-900">Lên lịch phỏng vấn</h3>
            <button type="button" data-close class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Đóng">&times;</button>
        </div>

        {{-- Edit-mode summary --}}
        <div data-edit-only class="hidden border-b border-slate-100 bg-slate-50 px-5 py-3 text-sm">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <a data-f="applicationUrl" class="font-semibold text-slate-900 hover:text-teal-700"></a>
                    <p data-f="job" class="truncate text-xs text-slate-500"></p>
                </div>
                <span data-f="statusLabel" class="rounded-full px-2.5 py-0.5 text-xs font-semibold"></span>
            </div>
        </div>

        <form method="POST" data-main-form action="{{ route('employer.interviews.store') }}" class="space-y-4 px-5 py-4">
            @csrf
            <input type="hidden" name="_method" value="POST" data-method>
            <input type="hidden" name="_interview" value="" data-interview-id>

            <label data-create-only class="block">
                <span class="form-label">Ứng viên <span class="text-red-500">*</span></span>
                <select name="application_id" class="form-input">
                    <option value="">— Chọn ứng viên đã ứng tuyển —</option>
                    @foreach ($applications as $application)
                        <option value="{{ $application->id }}" @selected((int) old('application_id', $preselect) === $application->id)>{{ $application->candidate->name }} · {{ $application->jobPost->title }}</option>
                    @endforeach
                </select>
                @error('application_id') <p class="form-error">{{ $message }}</p> @enderror
            </label>

            <div class="grid gap-4 sm:grid-cols-[1fr_140px]">
                <label class="block">
                    <span class="form-label">Thời gian <span class="text-red-500">*</span></span>
                    <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="form-input">
                    @error('scheduled_at') <p class="form-error">{{ $message }}</p> @enderror
                </label>
                <label class="block">
                    <span class="form-label">Thời lượng</span>
                    <select name="duration_minutes" class="form-input">
                        @foreach ([15, 30, 45, 60, 90, 120] as $minutes)
                            <option value="{{ $minutes }}" @selected((int) old('duration_minutes', 60) === $minutes)>{{ $minutes }} phút</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div>
                <span class="form-label">Hình thức <span class="text-red-500">*</span></span>
                <div class="mt-1 grid grid-cols-3 gap-2">
                    @foreach ($types as $type)
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="{{ $type->value }}" class="peer sr-only" @checked(old('type', 'online') === $type->value)>
                            <span class="block rounded-lg border border-slate-200 px-2 py-2 text-center text-sm font-medium text-slate-600 peer-checked:border-teal-600 peer-checked:bg-teal-50 peer-checked:text-teal-800">{{ $type->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <label class="block">
                <span class="form-label" data-location-label>Link phòng họp</span>
                <input type="text" name="location" value="{{ old('location') }}" class="form-input" placeholder="https://meet.google.com/...">
                @error('location') <p class="form-error">{{ $message }}</p> @enderror
            </label>

            <label class="block">
                <span class="form-label">Người phỏng vấn</span>
                <input type="text" name="interviewer" value="{{ old('interviewer') }}" class="form-input" placeholder="VD: Chị Lan – Trưởng phòng nhân sự">
            </label>

            <label class="block">
                <span class="form-label">Ghi chú cho ứng viên</span>
                <textarea name="note" rows="2" class="form-input" placeholder="Chuẩn bị portfolio, mang theo CMND...">{{ old('note') }}</textarea>
            </label>

            <p class="rounded-lg bg-teal-50 px-3 py-2 text-xs text-teal-800">Ứng viên sẽ nhận thông báo trên chuông khi bạn lên lịch, đổi giờ hoặc hủy lịch.</p>

            <div class="flex justify-end gap-2 pt-1">
                <button type="button" data-close class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Đóng</button>
                <button class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700" data-submit-label>Lên lịch</button>
            </div>
        </form>

        {{-- Edit-mode actions --}}
        <div data-edit-only class="hidden flex-wrap gap-2 border-t border-slate-100 px-5 py-3">
            <form method="POST" data-status-form>
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="completed">
                <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">✓ Đã phỏng vấn</button>
            </form>
            <form method="POST" data-status-form onsubmit="return confirm('Hủy buổi phỏng vấn này? Ứng viên sẽ nhận thông báo.')">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <button class="rounded-lg border border-amber-300 px-3 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50">Hủy lịch</button>
            </form>
            <form method="POST" data-destroy-form class="ml-auto" onsubmit="return confirm('Xóa hẳn lịch phỏng vấn này?')">
                @csrf @method('DELETE')
                <button class="rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Xóa</button>
            </form>
        </div>
    </dialog>

    <script>
        (() => {
            const data = @json($payload);
            const dialog = document.getElementById('interview-dialog');
            const form = dialog.querySelector('[data-main-form]');
            const badge = { scheduled: 'bg-teal-100 text-teal-700', completed: 'bg-slate-200 text-slate-600', cancelled: 'bg-red-100 text-red-600' };
            const locationLabels = { online: ['Link phòng họp', 'https://meet.google.com/...'], offline: ['Địa chỉ', 'Tầng 5, 123 Nguyễn Huệ, Q.1'], phone: ['Số điện thoại liên hệ (không bắt buộc)', '0901 234 567'] };

            const syncLocation = () => {
                const type = form.querySelector('input[name=type]:checked')?.value || 'online';
                dialog.querySelector('[data-location-label]').textContent = locationLabels[type][0];
                form.location.placeholder = locationLabels[type][1];
            };
            form.querySelectorAll('input[name=type]').forEach((r) => r.addEventListener('change', syncLocation));

            const setMode = (edit) => {
                dialog.querySelectorAll('[data-edit-only]').forEach((el) => { el.classList.toggle('hidden', !edit); el.classList.toggle('flex', edit && el.tagName === 'DIV' && el.matches('.flex-wrap')); });
                dialog.querySelectorAll('[data-create-only]').forEach((el) => el.classList.toggle('hidden', edit));
                form.application_id.disabled = edit;
                dialog.querySelector('[data-dialog-title]').textContent = edit ? 'Chi tiết lịch phỏng vấn' : 'Lên lịch phỏng vấn';
                dialog.querySelector('[data-submit-label]').textContent = edit ? 'Lưu thay đổi' : 'Lên lịch';
                form.querySelector('[data-method]').value = edit ? 'PUT' : 'POST';
            };

            const openCreate = (date, keepOld) => {
                setMode(false);
                form.action = @json(route('employer.interviews.store'));
                form.querySelector('[data-interview-id]').value = '';
                if (!keepOld) {
                    form.reset();
                    if (date) form.scheduled_at.value = date + 'T09:00';
                }
                syncLocation();
                dialog.showModal();
            };

            const openEdit = (id, keepOld) => {
                const item = data[id];
                if (!item) return;
                setMode(true);
                form.action = item.update;
                form.querySelector('[data-interview-id]').value = id;
                if (!keepOld) {
                    form.scheduled_at.value = item.scheduled_at;
                    form.duration_minutes.value = item.duration_minutes;
                    form.querySelector(`input[name=type][value=${item.type}]`).checked = true;
                    form.location.value = item.location || '';
                    form.interviewer.value = item.interviewer || '';
                    form.note.value = item.note || '';
                }
                const link = dialog.querySelector('[data-f=applicationUrl]');
                link.href = item.applicationUrl; link.textContent = item.candidate + ' · ' + item.when;
                dialog.querySelector('[data-f=job]').textContent = item.job + ' — ' + item.email;
                const status = dialog.querySelector('[data-f=statusLabel]');
                status.textContent = item.statusLabel; status.className = 'rounded-full px-2.5 py-0.5 text-xs font-semibold ' + badge[item.status];
                dialog.querySelectorAll('[data-status-form]').forEach((f) => { f.action = item.statusUrl; f.classList.toggle('hidden', item.status !== 'scheduled'); });
                dialog.querySelector('[data-destroy-form]').action = item.destroy;
                syncLocation();
                dialog.showModal();
            };

            document.querySelectorAll('[data-open-create]').forEach((b) => b.addEventListener('click', () => openCreate(b.dataset.date)));
            document.querySelectorAll('[data-open-edit]').forEach((b) => b.addEventListener('click', () => openEdit(b.dataset.openEdit)));
            dialog.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => dialog.close()));
            dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); });

            const reopen = @json($reopen);
            if (reopen === 'create') openCreate(null, true);
            else if (reopen?.startsWith('edit:')) openEdit(reopen.slice(5), true);
        })();
    </script>
@endsection
