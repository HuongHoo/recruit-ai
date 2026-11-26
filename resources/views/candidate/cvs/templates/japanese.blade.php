{{-- Japanese 履歴書 (rirekisho): bordered table form, history rows by year / month. --}}
@php
    $cell = 'border border-slate-700 px-2 py-1.5';
    $label = 'border border-slate-700 bg-slate-50 px-2 py-1 text-[11px] text-slate-600';
    /** Education then work history, oldest first, as rirekisho rows. */
    $history = collect()
        ->push(['heading' => '学歴 (Học vấn)'])
        ->merge($educations->sortBy('start_date')->flatMap(fn ($e) => array_filter([
            ['date' => $e->start_date, 'text' => $e->school.($e->major ? ' '.$e->major : '').' 入学'],
            $e->end_date ? ['date' => $e->end_date, 'text' => $e->school.' 卒業'.($e->degree ? ' ('.$e->degree.')' : '')] : null,
        ])))
        ->push(['heading' => '職歴 (Kinh nghiệm)'])
        ->merge($experiences->sortBy('start_date')->flatMap(fn ($x) => array_filter([
            ['date' => $x->start_date, 'text' => $x->company.' 入社 — '.$x->position],
            ! $x->is_current && $x->end_date ? ['date' => $x->end_date, 'text' => $x->company.' 退社'] : null,
        ])))
        ->when($experiences->contains('is_current', true), fn ($c) => $c->push(['date' => null, 'text' => '現在に至る (Đến nay)']))
        ->push(['date' => null, 'text' => '以上', 'right' => true]);
@endphp
<div class="px-[14mm] py-[12mm] text-[12.5px] text-slate-900">
    <div class="flex items-end justify-between">
        <h1 class="text-[26px] font-bold tracking-[0.4em]">履歴書</h1>
        <p class="text-[11px] text-slate-600">{{ now()->format('Y') }}年 {{ now()->format('n') }}月 {{ now()->format('j') }}日現在</p>
    </div>

    <table class="mt-3 w-full border-collapse">
        <tbody>
            <tr>
                <td class="{{ $label }} w-[22%]">ふりがな / Họ tên</td>
                <td class="{{ $cell }} text-[18px] font-bold">{{ $user->name }}</td>
                <td rowspan="3" class="{{ $cell }} w-[30mm] text-center align-middle text-[10px] text-slate-400">写真<br>(Ảnh 3x4)</td>
            </tr>
            <tr>
                <td class="{{ $label }}">生年月日 / Ngày sinh</td>
                <td class="{{ $cell }}">{{ $profile->date_of_birth ? $profile->date_of_birth->format('Y年n月j日').' (満'.$profile->date_of_birth->age.'歳)' : '' }}</td>
            </tr>
            <tr>
                <td class="{{ $label }}">現住所 / Địa chỉ</td>
                <td class="{{ $cell }}">{{ $profile->address }}</td>
            </tr>
            <tr>
                <td class="{{ $label }}">電話 / Điện thoại</td>
                <td colspan="2" class="{{ $cell }}">{{ $profile->phone }}</td>
            </tr>
            <tr>
                <td class="{{ $label }}">E-mail</td>
                <td colspan="2" class="{{ $cell }}">{{ $user->email }}</td>
            </tr>
        </tbody>
    </table>

    <table class="mt-4 w-full border-collapse">
        <thead>
            <tr>
                <th class="{{ $label }} w-[14%] text-center">年</th>
                <th class="{{ $label }} w-[8%] text-center">月</th>
                <th class="{{ $label }} text-center">学歴・職歴 (Quá trình học tập - làm việc)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($history as $row)
                <tr class="cv-section">
                    @isset($row['heading'])
                        <td class="{{ $cell }}"></td><td class="{{ $cell }}"></td>
                        <td class="{{ $cell }} text-center font-semibold">{{ $row['heading'] }}</td>
                    @else
                        <td class="{{ $cell }} text-center">{{ $row['date']?->format('Y') }}</td>
                        <td class="{{ $cell }} text-center">{{ $row['date']?->format('n') }}</td>
                        <td @class([$cell, 'text-right' => $row['right'] ?? false])>{{ $row['text'] }}</td>
                    @endisset
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="mt-4 w-full border-collapse">
        <tbody>
            <tr class="cv-section">
                <td class="{{ $label }} w-[22%]">免許・資格・スキル<br>Kỹ năng, chứng chỉ</td>
                <td class="{{ $cell }}">{{ $profile->skills ? implode('、', $profile->skills) : '' }}</td>
            </tr>
            <tr class="cv-section">
                <td class="{{ $label }}">志望動機・自己PR<br>Lý do ứng tuyển</td>
                <td class="{{ $cell }} leading-relaxed whitespace-pre-line">{{ $cv->objective ?: $profile->summary }}</td>
            </tr>
            <tr class="cv-section">
                <td class="{{ $label }}">本人希望記入欄<br>Nguyện vọng</td>
                <td class="{{ $cell }}">給与 / Lương: {{ $profile->salaryLabel() }}@if ($profile->desired_locations) · 勤務地 / Nơi làm việc: {{ implode(', ', $profile->desired_locations) }}@endif</td>
            </tr>
        </tbody>
    </table>
</div>
