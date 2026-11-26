{{-- ATS-friendly: single column, plain black text, standard headings, no graphics or colour blocks. --}}
<div class="px-[18mm] py-[16mm] text-[13px] text-slate-800">
    <header class="text-center">
        <h1 class="text-[26px] font-semibold text-slate-900">{{ $user->name }}</h1>
        @if ($profile->headline)
            <p class="mt-0.5 text-[13.5px] text-slate-700">{{ $profile->headline }}</p>
        @endif
        <p class="mt-2 text-[12px] text-slate-600">
            {{ collect([$profile->address, $user->email, $profile->phone, $profile->date_of_birth?->format('d/m/Y')])->filter()->join('   •   ') }}
        </p>
    </header>

    @php $heading = 'mt-6 mb-2.5 border-b border-slate-800 pb-1 text-[13px] font-bold tracking-wide text-slate-900 uppercase'; @endphp

    @if ($cv->objective || $profile->summary)
        <section class="cv-section mt-6">
            <p class="leading-relaxed whitespace-pre-line">{{ $cv->objective ?: $profile->summary }}</p>
        </section>
    @endif

    @if ($experiences->isNotEmpty())
        <section>
            <h2 class="{{ $heading }}">Kinh nghiệm làm việc</h2>
            <div class="space-y-3.5">
                @foreach ($experiences as $experience)
                    <div class="cv-section">
                        <div class="flex justify-between gap-4">
                            <p class="font-semibold text-slate-900">{{ $experience->position }}</p>
                            <span class="shrink-0 text-[12px] text-slate-600">{{ $experience->periodLabel() }}</span>
                        </div>
                        <p class="font-medium text-slate-600">{{ $experience->company }}</p>
                        @if ($experience->description)
                            <ul class="mt-1 list-disc space-y-0.5 pl-5 leading-relaxed">
                                @foreach (preg_split('/\r?\n/', trim($experience->description)) as $line)
                                    @if (trim($line, " -•\t") !== '')
                                        <li>{{ trim($line, " -•\t") }}</li>
                                    @endif
                                @endforeach
                            </ul>
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
                            <p class="font-semibold text-slate-900">{{ collect([$education->degree, $education->major])->filter()->join(' ') ?: $education->school }}</p>
                            <span class="shrink-0 text-[12px] text-slate-600">{{ $education->periodLabel() }}</span>
                        </div>
                        <p class="font-medium text-slate-600">{{ $education->school }}</p>
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
            <p class="leading-relaxed">{{ implode(', ', $profile->skills) }}</p>
        </section>
    @endif
</div>
