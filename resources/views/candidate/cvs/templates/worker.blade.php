{{-- Factory / manual-work CV: short, large text, contact details up front. --}}
@php $heading = 'mt-6 mb-2 rounded bg-(--accent) px-3 py-1.5 text-[14px] font-bold text-white uppercase'; @endphp
<div class="px-[18mm] py-[16mm] text-[15px] text-slate-800">
    <header class="flex items-start justify-between gap-6 border-b-2 border-slate-800 pb-4">
        <div>
            <p class="text-[13px] font-semibold text-slate-500 uppercase">Hồ sơ xin việc</p>
            <h1 class="mt-1 text-[30px] leading-tight font-extrabold text-slate-900 uppercase">{{ $user->name }}</h1>
            @if ($profile->headline)
                <p class="mt-1 text-[17px] font-semibold text-(--accent)">Ứng tuyển: {{ $profile->headline }}</p>
            @endif
        </div>
        <div class="shrink-0 rounded-lg border-2 border-slate-800 px-4 py-2 text-right">
            <p class="text-[12px] text-slate-500">Điện thoại</p>
            <p class="text-[20px] font-bold text-slate-900">{{ $profile->phone ?: '—' }}</p>
        </div>
    </header>

    <h2 class="{{ $heading }}">Thông tin cá nhân</h2>
    <dl class="grid grid-cols-[32mm_1fr] gap-y-1.5">
        @foreach (array_filter([
            'Ngày sinh' => $profile->date_of_birth?->format('d/m/Y'),
            'Giới tính' => $profile->gender ? config('recruitment.genders.'.$profile->gender) : null,
            'Địa chỉ' => $profile->address,
            'Email' => $user->email,
            'Nơi muốn làm' => $profile->desired_locations ? implode(', ', $profile->desired_locations) : null,
            'Mức lương' => $profile->salaryLabel(),
        ]) as $label => $value)
            <dt class="text-slate-500">{{ $label }}:</dt>
            <dd class="font-semibold text-slate-900">{{ $value }}</dd>
        @endforeach
    </dl>

    @if ($experiences->isNotEmpty())
        <section>
            <h2 class="{{ $heading }}">Kinh nghiệm làm việc</h2>
            <ul class="space-y-2.5">
                @foreach ($experiences as $experience)
                    <li class="cv-section">
                        <p class="font-bold text-slate-900">{{ $experience->position }} — {{ $experience->company }}</p>
                        <p class="text-[13px] text-slate-500">{{ $experience->periodLabel() }}</p>
                        @if ($experience->description)
                            <p class="mt-0.5 text-[14px] whitespace-pre-line">{{ $experience->description }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($educations->isNotEmpty())
        <section class="cv-section">
            <h2 class="{{ $heading }}">Học vấn</h2>
            @foreach ($educations as $education)
                <p><strong>{{ $education->degree ?: $education->school }}</strong>@if ($education->degree) — {{ $education->school }}@endif <span class="text-[13px] text-slate-500">({{ $education->periodLabel() }})</span></p>
            @endforeach
        </section>
    @endif

    @if ($profile->skills)
        <section class="cv-section">
            <h2 class="{{ $heading }}">Kỹ năng</h2>
            <ul class="grid grid-cols-2 gap-x-6 gap-y-1">
                @foreach ($profile->skills as $skill)
                    <li>✔ {{ $skill }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($cv->objective || $profile->summary)
        <section class="cv-section">
            <h2 class="{{ $heading }}">Cam kết</h2>
            <p class="leading-relaxed whitespace-pre-line">{{ $cv->objective ?: $profile->summary }}</p>
        </section>
    @endif
</div>
