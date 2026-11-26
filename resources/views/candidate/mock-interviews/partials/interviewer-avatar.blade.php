{{--
    Animated AI interviewer sitting in an office (16:9 scene).
    Props: $gender ('female'|'male'), $name, $uid (unique per page), $class, $nameplate (desk name sign, default true).
    The session script drives it through classes on the root: is-speaking, is-listening, is-nodding,
    and the mouth through [data-mouth-open] / [data-mouth-closed].
--}}
@php
    $female = $gender === 'female';
    $skin = '#f3c9a5';
    $skinShade = '#e2ad86';
    $hair = $female ? '#3b2418' : '#1f1b18';
    $jacket = $female ? '#1e3a8a' : '#334155';
    $lapel = $female ? '#172e6e' : '#283447';
@endphp

@once
    <style>
        .ai-interviewer .iv-eyes { transform-box: fill-box; transform-origin: center; animation: iv-blink 5.2s infinite; }
        .ai-interviewer .iv-person { animation: iv-breathe 4.5s ease-in-out infinite; }
        .ai-interviewer .iv-head { transform-box: view-box; transform-origin: 320px 240px; transition: transform .5s ease; }
        .ai-interviewer .iv-brows, .ai-interviewer .iv-pupils { transition: transform .35s ease; }
        .ai-interviewer.is-speaking .iv-head { animation: iv-talk 3s ease-in-out infinite; }
        .ai-interviewer.is-listening .iv-head { transform: rotate(-3.5deg); }
        .ai-interviewer.is-listening .iv-brows { transform: translateY(-2.5px); }
        .ai-interviewer.is-listening .iv-pupils { transform: translate(-1.5px, 1px); }
        .ai-interviewer.is-nodding .iv-head { animation: iv-nod .9s ease-in-out; }
        @keyframes iv-blink { 0%, 93%, 100% { transform: scaleY(1); } 95% { transform: scaleY(.1); } }
        @keyframes iv-breathe { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-2px); } }
        @keyframes iv-talk { 0%, 100% { transform: rotate(0); } 25% { transform: rotate(1.6deg); } 55% { transform: rotate(-1.2deg) translateY(1px); } 80% { transform: rotate(.6deg); } }
        @keyframes iv-nod { 0%, 100% { transform: translateY(0); } 25% { transform: translateY(6px); } 50% { transform: translateY(-1px); } 75% { transform: translateY(4px); } }
        @media (prefers-reduced-motion: reduce) {
            .ai-interviewer .iv-eyes, .ai-interviewer .iv-person, .ai-interviewer.is-speaking .iv-head, .ai-interviewer.is-nodding .iv-head { animation: none; }
        }
    </style>
@endonce

<svg data-avatar viewBox="0 0 640 360" preserveAspectRatio="xMidYMid slice" class="ai-interviewer {{ $class ?? '' }}" role="img" aria-label="Người phỏng vấn AI {{ $name }}">
    <defs>
        <linearGradient id="{{ $uid }}-wall" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e8edf3"/><stop offset="1" stop-color="#cfd8e3"/></linearGradient>
        <linearGradient id="{{ $uid }}-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9cc8f5"/><stop offset="1" stop-color="#dcefff"/></linearGradient>
        <linearGradient id="{{ $uid }}-desk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#a26a3c"/><stop offset="1" stop-color="#7a4a26"/></linearGradient>
        <radialGradient id="{{ $uid }}-light" cx=".5" cy=".35" r=".6"><stop offset="0" stop-color="#fff" stop-opacity=".55"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></radialGradient>
    </defs>

    {{-- Office --}}
    <rect width="640" height="360" fill="url(#{{ $uid }}-wall)"/>
    <g>
        <rect x="36" y="36" width="176" height="210" rx="6" fill="url(#{{ $uid }}-sky)"/>
        <g fill="#7fb0e3" opacity=".75">
            <rect x="46" y="150" width="26" height="96"/><rect x="76" y="120" width="30" height="126"/><rect x="110" y="168" width="22" height="78"/>
            <rect x="136" y="134" width="34" height="112"/><rect x="174" y="160" width="28" height="86"/>
        </g>
        <g fill="#fff" opacity=".6">
            <rect x="82" y="130" width="5" height="6"/><rect x="94" y="130" width="5" height="6"/><rect x="82" y="146" width="5" height="6"/><rect x="144" y="146" width="5" height="6"/><rect x="156" y="146" width="5" height="6"/><rect x="144" y="162" width="5" height="6"/>
        </g>
        <rect x="36" y="36" width="176" height="210" rx="6" fill="none" stroke="#f8fafc" stroke-width="8"/>
        <path d="M124 36v210M36 140h176" stroke="#f8fafc" stroke-width="5"/>
    </g>
    <g>
        <rect x="452" y="44" width="160" height="236" rx="4" fill="#8b5a32"/>
        <rect x="460" y="52" width="144" height="220" fill="#6e4424"/>
        <path d="M460 122h144M460 196h144" stroke="#8b5a32" stroke-width="7"/>
        <g>
            <rect x="468" y="72" width="12" height="46" fill="#ef4444"/><rect x="482" y="78" width="10" height="40" fill="#f59e0b"/><rect x="494" y="68" width="14" height="50" fill="#3b82f6"/>
            <rect x="510" y="80" width="10" height="38" fill="#10b981"/><rect x="530" y="84" width="40" height="8" fill="#e2e8f0" transform="rotate(-18 550 88)"/>
            <rect x="572" y="74" width="12" height="44" fill="#6366f1"/>
            <rect x="468" y="148" width="14" height="44" fill="#0ea5e9"/><rect x="484" y="154" width="10" height="38" fill="#f43f5e"/><rect x="496" y="146" width="12" height="46" fill="#eab308"/>
            <circle cx="560" cy="176" r="15" fill="#22c55e"/><circle cx="550" cy="168" r="10" fill="#16a34a"/><circle cx="571" cy="166" r="9" fill="#15803d"/><rect x="551" y="178" width="18" height="15" rx="3" fill="#e2e8f0"/>
            <rect x="472" y="222" width="54" height="40" rx="3" fill="#f1f5f9"/><rect x="478" y="228" width="42" height="28" fill="#93c5fd"/>
            <rect x="540" y="216" width="12" height="46" fill="#a855f7"/><rect x="554" y="222" width="10" height="40" fill="#14b8a6"/><rect x="566" y="214" width="14" height="48" fill="#f97316"/>
        </g>
    </g>
    <rect width="640" height="360" fill="url(#{{ $uid }}-light)"/>

    {{-- Interviewer --}}
    <g class="iv-person">
        @if ($female)
            <g class="iv-head">
                <path d="M258 160c-6-62 30-80 62-80s70 16 64 80l8 116c-24 16-120 16-142 0z" fill="{{ $hair }}"/>
            </g>
        @endif

        <path d="M186 360c2-62 32-94 84-102h100c52 8 82 40 84 102z" fill="{{ $jacket }}"/>
        <rect x="302" y="210" width="36" height="58" rx="14" fill="{{ $skinShade }}"/>
        @if ($female)
            <path d="M292 258l28 50 28-50z" fill="#f8fafc"/>
            <circle cx="320" cy="270" r="4" fill="#fbbf24"/>
        @else
            <path d="M290 258l30 46 30-46z" fill="#f8fafc"/>
            <path d="M314 264h12l5 40-11 22-11-22z" fill="#2563eb"/>
            <path d="M314 264h12l-2 8h-8z" fill="#1d4ed8"/>
        @endif
        <path d="M270 260l50 66-24 34h-26l-10-96z" fill="{{ $lapel }}"/>
        <path d="M370 260l-50 66 24 34h26l10-96z" fill="{{ $lapel }}"/>

        <g class="iv-head">
            <ellipse cx="263" cy="170" rx="9" ry="14" fill="{{ $skinShade }}"/>
            <ellipse cx="377" cy="170" rx="9" ry="14" fill="{{ $skinShade }}"/>
            @if ($female)
                <circle cx="263" cy="186" r="3" fill="#fbbf24"/><circle cx="377" cy="186" r="3" fill="#fbbf24"/>
            @endif
            <ellipse cx="320" cy="160" rx="56" ry="66" fill="{{ $skin }}"/>

            @if ($female)
                <path d="M262 156c-2-50 26-70 58-70 34 0 60 20 60 70-12-26-36-42-66-40-20 4-40 18-52 40z" fill="{{ $hair }}"/>
                <path d="M262 150c-8 40-4 80-12 118h18c2-40-2-80 2-114z" fill="{{ $hair }}"/>
                <path d="M380 150c8 40 4 80 12 118h-18c-2-40 2-80-2-114z" fill="{{ $hair }}"/>
            @else
                <path d="M264 152c-6-52 30-66 58-66 36 0 62 16 55 66-5-20-16-32-36-36-24 8-48 6-66 14-6 6-9 14-11 22z" fill="{{ $hair }}"/>
            @endif

            <g class="iv-brows" stroke="{{ $hair }}" stroke-width="{{ $female ? 3 : 4 }}" stroke-linecap="round" fill="none">
                <path d="M289 140q11-7 22-1"/><path d="M329 139q11-6 22 1"/>
            </g>
            <g class="iv-eyes">
                <ellipse cx="300" cy="160" rx="6.5" ry="7.5" fill="#fff"/><ellipse cx="340" cy="160" rx="6.5" ry="7.5" fill="#fff"/>
                <g class="iv-pupils">
                    <circle cx="300" cy="161" r="4.6" fill="#2b1d16"/><circle cx="340" cy="161" r="4.6" fill="#2b1d16"/>
                    <circle cx="301.6" cy="159.2" r="1.5" fill="#fff"/><circle cx="341.6" cy="159.2" r="1.5" fill="#fff"/>
                </g>
                @if ($female)
                    <path d="M292 154q8-5 16 0M332 154q8-5 16 0" stroke="#2b1d16" stroke-width="2" fill="none" stroke-linecap="round"/>
                @endif
            </g>
            @unless ($female)
                <g stroke="#1f2937" stroke-width="2.5" fill="none">
                    <rect x="285" y="148" width="30" height="24" rx="8"/><rect x="325" y="148" width="30" height="24" rx="8"/>
                    <path d="M315 158q5-4 10 0M285 156l-21-4M355 156l21-4"/>
                </g>
            @endunless

            <path d="M320 166q-5 15 0 20 4 1 8-1" stroke="#cf9670" stroke-width="2.2" fill="none" stroke-linecap="round"/>
            <circle cx="289" cy="188" r="9" fill="#f59e9e" opacity="{{ $female ? '.4' : '.18' }}"/>
            <circle cx="351" cy="188" r="9" fill="#f59e9e" opacity="{{ $female ? '.4' : '.18' }}"/>

            <path data-mouth-closed d="M306 202q14 10 28 0" stroke="{{ $female ? '#c2414b' : '#9a4a3c' }}" stroke-width="3.2" fill="none" stroke-linecap="round"/>
            <g data-mouth-open transform="translate(0 199) scale(1 0) translate(0 -199)">
                <path d="M306 199q14 -2 28 0q-2 16 -14 16t-14 -16z" fill="#7f1d1d" stroke="{{ $female ? '#c2414b' : '#9a4a3c' }}" stroke-width="2"/>
                <rect x="311" y="199" width="18" height="3.5" rx="1.5" fill="#fff"/>
            </g>
        </g>
    </g>

    {{-- Desk --}}
    <rect x="0" y="322" width="640" height="38" fill="url(#{{ $uid }}-desk)"/>
    <rect x="0" y="318" width="640" height="6" fill="#b47a48"/>
    <g>
        <path d="M120 322l8-28h44l8 28z" fill="#f8fafc"/><path d="M128 294h44" stroke="#cbd5e1" stroke-width="2"/>
        <rect x="190" y="298" width="22" height="24" rx="4" fill="#f1f5f9"/><path d="M212 304q10 0 10 7t-10 7" stroke="#f1f5f9" stroke-width="4" fill="none"/>
    </g>
    @if ($nameplate ?? true)
        <g>
            <path d="M420 322l10-30h120l10 30z" fill="#1e293b"/>
            <text x="490" y="313" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif" font-size="13" font-weight="700" fill="#f8fafc">{{ $name }}</text>
        </g>
    @endif
</svg>
