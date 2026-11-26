{{-- Shared shell for the public legal pages (privacy policy, terms). Google's OAuth consent screen links to them. --}}
<div class="bg-slate-50">
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <h1 class="text-3xl font-extrabold text-slate-900">{{ $heading }}</h1>
        <p class="mt-2 text-sm text-slate-500">Cập nhật lần cuối: {{ $updatedAt }}</p>

        <div class="mt-8 space-y-8 rounded-2xl border border-slate-200 bg-white p-6 text-[15px] leading-relaxed text-slate-700 sm:p-10">
            @foreach ($sections as $title => $paragraphs)
                <section>
                    <h2 class="text-lg font-bold text-slate-900">{{ $loop->iteration }}. {{ $title }}</h2>
                    @foreach ((array) $paragraphs as $paragraph)
                        @if (is_array($paragraph))
                            <ul class="mt-3 list-disc space-y-1.5 pl-6">
                                @foreach ($paragraph as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3">{{ $paragraph }}</p>
                        @endif
                    @endforeach
                </section>
            @endforeach

            <section class="border-t border-slate-200 pt-6 text-sm">
                Mọi thắc mắc xin gửi về
                <a href="mailto:{{ config('mail.from.address') }}" class="font-semibold text-blue-600 hover:underline">{{ config('mail.from.address') }}</a>.
                Xem thêm
                @if (request()->routeIs('legal.privacy'))
                    <a href="{{ route('legal.terms') }}" class="font-semibold text-blue-600 hover:underline">Điều khoản sử dụng</a>.
                @else
                    <a href="{{ route('legal.privacy') }}" class="font-semibold text-blue-600 hover:underline">Chính sách bảo mật</a>.
                @endif
            </section>
        </div>
    </article>
</div>
