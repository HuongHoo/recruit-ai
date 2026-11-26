{{-- Bilingual Chinese / Vietnamese headings, 个人简历 layout with an info table. --}}
@php
    $heading = function (string $zh, string $vi): string {
        return '<h2 class="mt-6 mb-3 flex items-baseline gap-2 border-l-4 border-(--accent) bg-slate-50 py-1.5 pl-3"><span class="text-[15px] font-bold text-slate-900">'.$zh.'</span><span class="text-[11px] text-slate-500 uppercase">'.e($vi).'</span></h2>';
    };
@endphp
<div class="px-[16mm] py-[14mm] text-[13px] text-slate-800">
    <header class="border-b-2 border-(--accent) pb-4 text-center">
        <p class="text-[24px] font-bold tracking-[0.5em] text-(--accent)">个人简历</p>
        <p class="text-[11px] tracking-widest text-slate-500 uppercase">Sơ yếu lý lịch</p>
    </header>

    {!! $heading('基本信息', 'Thông tin cá nhân') !!}
    <table class="w-full border-collapse text-[12.5px]">
        <tbody>
            @foreach (array_chunk(array_filter([
                ['姓名 / Họ tên', $user->name],
                ['出生日期 / Ngày sinh', $profile->date_of_birth?->format('d/m/Y')],
                ['电话 / Điện thoại', $profile->phone],
                ['邮箱 / Email', $user->email],
                ['地址 / Địa chỉ', $profile->address],
                ['求职意向 / Vị trí', $profile->headline],
            ], fn ($row) => filled($row[1])), 2) as $pair)
                <tr>
                    @foreach ($pair as [$label, $value])
                        <th class="w-[18%] border border-slate-300 bg-slate-50 px-2 py-1.5 text-left font-medium text-slate-600">{{ $label }}</th>
                        <td class="border border-slate-300 px-2 py-1.5 font-medium text-slate-900">{{ $value }}</td>
                    @endforeach
                    @if (count($pair) === 1) <td colspan="2" class="border border-slate-300"></td> @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($cv->objective || $profile->summary)
        <section class="cv-section">
            {!! $heading('自我评价', 'Giới thiệu bản thân') !!}
            <p class="leading-relaxed whitespace-pre-line">{{ $cv->objective ?: $profile->summary }}</p>
        </section>
    @endif

    @if ($experiences->isNotEmpty())
        <section>
            {!! $heading('工作经历', 'Kinh nghiệm làm việc') !!}
            <div class="space-y-3">
                @foreach ($experiences as $experience)
                    <div class="cv-section">
                        <div class="flex justify-between gap-4">
                            <p><strong class="text-slate-900">{{ $experience->company }}</strong> · {{ $experience->position }}</p>
                            <span class="shrink-0 text-[12px] text-slate-500">{{ $experience->periodLabel() }}</span>
                        </div>
                        @if ($experience->description)
                            <p class="mt-1 leading-relaxed whitespace-pre-line text-slate-700">{{ $experience->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($educations->isNotEmpty())
        <section>
            {!! $heading('教育背景', 'Học vấn') !!}
            <div class="space-y-2">
                @foreach ($educations as $education)
                    <div class="cv-section flex justify-between gap-4">
                        <p><strong class="text-slate-900">{{ $education->school }}</strong>{{ collect([$education->major, $education->degree])->filter()->map(fn ($v) => ' · '.$v)->join('') }}</p>
                        <span class="shrink-0 text-[12px] text-slate-500">{{ $education->periodLabel() }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($profile->skills)
        <section class="cv-section">
            {!! $heading('专业技能', 'Kỹ năng') !!}
            <p class="leading-relaxed">{{ implode(' · ', $profile->skills) }}</p>
        </section>
    @endif

    <section class="cv-section">
        {!! $heading('期望待遇', 'Mong muốn') !!}
        <p>薪资 / Mức lương: {{ $profile->salaryLabel() }}@if ($profile->desired_locations) · 工作地点 / Nơi làm việc: {{ implode(', ', $profile->desired_locations) }}@endif</p>
    </section>
</div>
