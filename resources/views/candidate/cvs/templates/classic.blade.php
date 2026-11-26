{{-- Single column, centred header, formal look. --}}
<div class="px-[18mm] py-[16mm] font-serif text-[13.5px] text-slate-800">
    <header class="text-center">
        <h1 class="text-[30px] font-bold tracking-wide text-slate-900 uppercase">{{ $user->name }}</h1>
        @if ($profile->headline)
            <p class="mt-1 text-base text-(--accent) italic">{{ $profile->headline }}</p>
        @endif
        <p class="mt-3 text-[12.5px] text-slate-600">
            {{ collect([
                $user->email,
                $profile->phone,
                $profile->date_of_birth?->format('d/m/Y'),
                $profile->address,
            ])->filter()->join('  •  ') }}
        </p>
    </header>

    @php
        $heading = 'mt-7 mb-3 border-b-2 border-(--accent) pb-1 text-[13px] font-bold tracking-[0.2em] text-(--accent) uppercase';
    @endphp

    @if ($cv->objective || $profile->summary)
        <section class="cv-section">
            <h2 class="{{ $heading }}">{{ $cv->objective ? 'Mục tiêu nghề nghiệp' : 'Giới thiệu' }}</h2>
            <p class="leading-relaxed whitespace-pre-line">{{ $cv->objective ?: $profile->summary }}</p>
        </section>
    @endif

    @if ($experiences->isNotEmpty())
        <section>
            <h2 class="{{ $heading }}">Kinh nghiệm làm việc</h2>
            <div class="space-y-4">
                @foreach ($experiences as $experience)
                    <div class="cv-section">
                        <div class="flex justify-between gap-4">
                            <p><strong class="text-slate-900">{{ $experience->company }}</strong> — {{ $experience->position }}</p>
                            <span class="shrink-0 text-slate-500 italic">{{ $experience->periodLabel() }}</span>
                        </div>
                        @if ($experience->description)
                            <p class="mt-1 leading-relaxed whitespace-pre-line">{{ $experience->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($educations->isNotEmpty())
        <section>
            <h2 class="{{ $heading }}">Học vấn</h2>
            <div class="space-y-3">
                @foreach ($educations as $education)
                    <div class="cv-section">
                        <div class="flex justify-between gap-4">
                            <p><strong class="text-slate-900">{{ $education->school }}</strong>@if ($education->major) — {{ $education->major }}@endif</p>
                            <span class="shrink-0 text-slate-500 italic">{{ $education->periodLabel() }}</span>
                        </div>
                        @if ($education->degree) <p class="text-slate-600">{{ $education->degree }}</p> @endif
                        @if ($education->description)
                            <p class="mt-1 whitespace-pre-line">{{ $education->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($profile->skills)
        <section class="cv-section">
            <h2 class="{{ $heading }}">Kỹ năng</h2>
            <p>{{ implode(' • ', $profile->skills) }}</p>
        </section>
    @endif

    <section class="cv-section">
        <h2 class="{{ $heading }}">Mong muốn công việc</h2>
        <p>Mức lương: {{ $profile->salaryLabel() }}@if ($profile->desired_locations) · Nơi làm việc: {{ implode(', ', $profile->desired_locations) }}@endif</p>
    </section>
</div>
