{{-- Airy layout: label column on the left, content on the right. --}}
<div class="px-[20mm] py-[20mm] text-[13.5px] text-slate-700">
    <header>
        <h1 class="text-[40px] leading-none font-light tracking-tight text-slate-900">{{ $user->name }}</h1>
        <div class="mt-4 h-1 w-16 bg-(--accent)"></div>
        @if ($profile->headline)
            <p class="mt-4 text-lg text-slate-600">{{ $profile->headline }}</p>
        @endif
        <p class="mt-2 text-[12.5px] text-slate-500">
            {{ collect([$user->email, $profile->phone, $profile->address])->filter()->join('   /   ') }}
        </p>
    </header>

    <div class="mt-12 space-y-10">
        @if ($cv->objective || $profile->summary)
            <section class="cv-section grid grid-cols-[38mm_1fr] gap-6">
                <h2 class="text-xs font-semibold tracking-widest text-(--accent) uppercase">Giới thiệu</h2>
                <p class="leading-relaxed whitespace-pre-line">{{ $cv->objective ?: $profile->summary }}</p>
            </section>
        @endif

        @if ($experiences->isNotEmpty())
            <section class="grid grid-cols-[38mm_1fr] gap-6">
                <h2 class="text-xs font-semibold tracking-widest text-(--accent) uppercase">Kinh nghiệm</h2>
                <div class="space-y-6">
                    @foreach ($experiences as $experience)
                        <div class="cv-section">
                            <p class="text-xs text-slate-400">{{ $experience->periodLabel() }}</p>
                            <h3 class="mt-0.5 font-semibold text-slate-900">{{ $experience->position }} <span class="font-normal text-slate-500">· {{ $experience->company }}</span></h3>
                            @if ($experience->description)
                                <p class="mt-1.5 leading-relaxed whitespace-pre-line">{{ $experience->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($educations->isNotEmpty())
            <section class="grid grid-cols-[38mm_1fr] gap-6">
                <h2 class="text-xs font-semibold tracking-widest text-(--accent) uppercase">Học vấn</h2>
                <div class="space-y-5">
                    @foreach ($educations as $education)
                        <div class="cv-section">
                            <p class="text-xs text-slate-400">{{ $education->periodLabel() }}</p>
                            <h3 class="mt-0.5 font-semibold text-slate-900">{{ $education->school }}</h3>
                            <p class="text-slate-500">{{ collect([$education->degree, $education->major])->filter()->join(' · ') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($profile->skills)
            <section class="cv-section grid grid-cols-[38mm_1fr] gap-6">
                <h2 class="text-xs font-semibold tracking-widest text-(--accent) uppercase">Kỹ năng</h2>
                <div class="flex flex-wrap gap-x-5 gap-y-1.5">
                    @foreach ($profile->skills as $skill)
                        <span>{{ $skill }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="cv-section grid grid-cols-[38mm_1fr] gap-6">
            <h2 class="text-xs font-semibold tracking-widest text-(--accent) uppercase">Mong muốn</h2>
            <p>{{ $profile->salaryLabel() }}@if ($profile->desired_locations) — {{ implode(', ', $profile->desired_locations) }}@endif</p>
        </section>
    </div>
</div>
