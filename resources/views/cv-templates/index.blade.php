@extends('layouts.public')
@section('title', 'Mẫu CV')

@section('content')
    <section class="bg-gradient-to-b from-blue-50 to-white">
        <div class="mx-auto max-w-7xl px-4 py-12 text-center sm:px-6">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Mẫu CV xin việc chuyên nghiệp</h1>
            <p class="mx-auto mt-3 max-w-2xl text-slate-600">Chọn mẫu, điền hồ sơ một lần — hệ thống tự đưa nội dung vào mẫu. Đổi mẫu bất cứ lúc nào và tải PDF miễn phí.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($templates as $key => $template)
                @php $cv->template = $key; @endphp
                <article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-blue-300 hover:shadow-xl">
                    <a href="{{ route('cv-templates.show', $key) }}" class="relative block h-80 overflow-hidden border-b border-slate-100 bg-slate-50 pt-4" aria-label="Xem mẫu {{ $template['name'] }}">
                        {{-- Live miniature: the real template rendered with sample data, zoomed out --}}
                        <div class="pointer-events-none mx-auto w-[210mm] bg-white shadow-md [zoom:0.33]" style="--accent: {{ $cv->accent_color }}" aria-hidden="true">
                            @include('candidate.cvs.templates.'.$key)
                        </div>
                        <span class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-white"></span>
                    </a>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-center gap-2">
                            <h2 class="font-semibold text-slate-900">{{ $template['name'] }}</h2>
                            @if ($template['ats'])
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Thân thiện ATS</span>
                            @endif
                        </div>
                        <p class="mt-1 flex-1 text-sm text-slate-500">{{ $template['description'] }}</p>
                        <a href="{{ route('cv-templates.show', $key) }}" class="btn-secondary mt-4 py-2">Xem mẫu & dùng</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection
