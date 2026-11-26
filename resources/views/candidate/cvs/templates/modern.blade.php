{{-- Two columns: coloured sidebar with contact/skills, main column with experience/education. --}}
<div class="flex min-h-[297mm]">
    <aside class="w-[72mm] shrink-0 space-y-7 bg-(--accent) px-7 py-10 text-white">
        <div class="text-center">
            <div class="mx-auto grid size-28 place-items-center rounded-full border-4 border-white/40 bg-white/15 text-4xl font-bold">
                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
            </div>
        </div>

        <div class="cv-section">
            <h3 class="border-b border-white/30 pb-1.5 text-xs font-bold tracking-widest uppercase">Liên hệ</h3>
            <ul class="mt-3 space-y-2 text-[13px] break-words text-white/90">
                <li>✉ {{ $user->email }}</li>
                @if ($profile->phone) <li>☎ {{ $profile->phone }}</li> @endif
                @if ($profile->date_of_birth) <li>🎂 {{ $profile->date_of_birth->format('d/m/Y') }}</li> @endif
                @if ($profile->genderLabel()) <li>⚥ {{ $profile->genderLabel() }}</li> @endif
                @if ($profile->address) <li>⌂ {{ $profile->address }}</li> @endif
            </ul>
        </div>

        @if ($profile->skills)
            <div class="cv-section">
                <h3 class="border-b border-white/30 pb-1.5 text-xs font-bold tracking-widest uppercase">Kỹ năng</h3>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($profile->skills as $skill)
                        <span class="rounded bg-white/20 px-2 py-0.5 text-[12px]">{{ $skill }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="cv-section">
            <h3 class="border-b border-white/30 pb-1.5 text-xs font-bold tracking-widest uppercase">Mong muốn</h3>
            <ul class="mt-3 space-y-1.5 text-[13px] text-white/90">
                <li>Mức lương: {{ $profile->salaryLabel() }}</li>
                @if ($profile->desired_locations)
                    <li>Nơi làm việc: {{ implode(', ', $profile->desired_locations) }}</li>
                @endif
            </ul>
        </div>
    </aside>

    <main class="flex-1 space-y-7 px-9 py-10">
        <header>
            <h1 class="text-[32px] leading-tight font-extrabold text-slate-900">{{ $user->name }}</h1>
            @if ($profile->headline)
                <p class="mt-1 text-lg font-medium text-(--accent)">{{ $profile->headline }}</p>
            @endif
        </header>

        @if ($cv->objective || $profile->summary)
            <section class="cv-section">
                <h2 class="text-sm font-bold tracking-widest text-(--accent) uppercase">{{ $cv->objective ? 'Mục tiêu nghề nghiệp' : 'Giới thiệu' }}</h2>
                <p class="mt-2 text-[13.5px] leading-relaxed whitespace-pre-line text-slate-700">{{ $cv->objective ?: $profile->summary }}</p>
            </section>
        @endif

        @if ($experiences->isNotEmpty())
            <section>
                <h2 class="text-sm font-bold tracking-widest text-(--accent) uppercase">Kinh nghiệm làm việc</h2>
                <div class="mt-3 space-y-4 border-l-2 border-slate-200 pl-4">
                    @foreach ($experiences as $experience)
                        <div class="cv-section relative">
                            <span class="absolute top-1.5 -left-[21px] size-2.5 rounded-full bg-(--accent)"></span>
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 class="font-bold text-slate-900">{{ $experience->position }}</h3>
                                <span class="shrink-0 text-xs text-slate-500">{{ $experience->periodLabel() }}</span>
                            </div>
                            <p class="text-[13px] font-medium text-slate-600">{{ $experience->company }}</p>
                            @if ($experience->description)
                                <p class="mt-1 text-[13px] leading-relaxed whitespace-pre-line text-slate-700">{{ $experience->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($educations->isNotEmpty())
            <section>
                <h2 class="text-sm font-bold tracking-widest text-(--accent) uppercase">Học vấn</h2>
                <div class="mt-3 space-y-3">
                    @foreach ($educations as $education)
                        <div class="cv-section">
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 class="font-bold text-slate-900">{{ $education->school }}</h3>
                                <span class="shrink-0 text-xs text-slate-500">{{ $education->periodLabel() }}</span>
                            </div>
                            <p class="text-[13px] text-slate-600">{{ collect([$education->degree, $education->major])->filter()->join(' · ') }}</p>
                            @if ($education->description)
                                <p class="mt-1 text-[13px] whitespace-pre-line text-slate-700">{{ $education->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</div>
