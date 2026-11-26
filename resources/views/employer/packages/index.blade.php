@extends('layouts.app')
@section('title', 'Gói dịch vụ')
@section('subtitle', 'Đưa tin tuyển dụng đến nhiều ứng viên hơn, thanh toán theo từng tin.')
@section('icon', 'receipt')

@php
    $money = fn (int $v) => number_format($v, 0, ',', '.').' đ';
    $cartCount = $cart->sum('quantity');
    $priorityMax = 4;
@endphp

@section('content')
    @error('cart') <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div> @enderror

    {{-- Hero --}}
    <section class="overflow-hidden rounded-2xl border border-teal-800/30">
        <div class="relative bg-gradient-to-r from-[#0b1724] via-[#0f3b3e] to-teal-700 px-6 py-6 text-white sm:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <span class="rounded-full border border-teal-300/50 bg-teal-400/10 px-2.5 py-0.5 text-[11px] font-semibold text-teal-200">Dịch vụ đẩy tin tuyển dụng</span>
                    <h2 class="mt-2 text-2xl font-bold">Chọn gói giúp tin của bạn nổi bật hơn</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-200">Chọn gói để tin tuyển dụng được ưu tiên hiển thị, tiếp cận nhiều ứng viên tiềm năng hơn hoặc đăng tin miễn phí. Thanh toán theo từng tin, không tự động gia hạn.</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <a href="#so-sanh" class="rounded-lg border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-semibold hover:bg-white/20">So sánh quyền lợi từng gói ⌄</a>
                    <a href="{{ route('employer.packages.cart') }}" class="relative rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-teal-800 hover:bg-teal-50">
                        🛒 Giỏ hàng
                        @if ($cartCount) <span class="absolute -top-2 -right-2 grid min-w-5 place-items-center rounded-full bg-orange-500 px-1 text-[11px] leading-5 text-white">{{ $cartCount }}</span> @endif
                    </a>
                </div>
            </div>
        </div>
        <ol class="grid gap-3 bg-white px-5 py-4 md:grid-cols-3 md:divide-x md:divide-slate-200">
            @foreach ([
                ['Chọn gói & thời hạn', 'Chọn cấp ưu tiên (Basic, Pro, Pro Max) và số ngày hiển thị.'],
                ['Thanh toán hoặc gộp giỏ', 'Mua ngay hoặc gộp nhiều gói thanh toán một lần qua VietQR.'],
                ['Kích hoạt khi cần', 'Gói lưu trong Gói đã mua; chỉ tính ngày khi gắn vào tin.'],
            ] as $i => [$title, $text])
                <li class="flex gap-3 md:px-4 md:first:pl-0">
                    <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-teal-700 text-sm font-bold text-white">{{ $i + 1 }}</span>
                    <span><span class="block text-sm font-semibold text-slate-900">{{ $title }}</span><span class="block text-xs text-slate-500">{{ $text }}</span></span>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Plans --}}
    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {{-- Free --}}
        <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="text-lg font-bold text-slate-900">Free</h3>
            <p class="mt-3 text-3xl font-bold text-slate-900">Miễn phí</p>
            <p class="mt-1 text-xs text-slate-500">/ tin</p>
            <p class="mt-4 text-sm text-slate-600">Phù hợp khi bạn mới bắt đầu tuyển.</p>
            <p class="mt-auto pt-10 text-xs text-slate-500">Không cần thanh toán, đăng tin được ngay.</p>
            <a href="{{ route('employer.jobs.create') }}" class="mt-3 rounded-lg border border-slate-300 py-2.5 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">Đăng tin miễn phí</a>
            @include('employer.packages._benefits', ['level' => 1, 'rank' => 'Hiển thị sau các tin trả phí', 'has' => []])
        </article>

        @foreach ($packages as $tier => $package)
            @php $days = isset($package['prices'][14]) ? 14 : array_key_first($package['prices']); @endphp
            <article data-plan @class(['relative flex flex-col rounded-2xl border bg-white p-5',
                'border-teal-600 bg-gradient-to-b from-teal-50/70 to-white shadow-lg shadow-teal-900/5' => $package['recommended'] ?? false,
                'border-slate-200' => ! ($package['recommended'] ?? false)])>
                @if ($package['recommended'] ?? false)
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-teal-700 px-3 py-0.5 text-[11px] font-semibold text-white">Gói đề xuất</span>
                @endif
                <h3 class="text-lg font-bold text-slate-900">{{ $package['name'] }}</h3>
                <p @class(['mt-3 text-3xl font-bold', 'text-teal-700' => $package['recommended'] ?? false, 'text-slate-900' => ! ($package['recommended'] ?? false)]) data-price>{{ $money($package['prices'][$days]) }}</p>
                <p class="mt-1 text-xs text-slate-500">/ tin · <span data-days-label>{{ $days }}</span> ngày</p>
                <p class="mt-4 text-sm text-slate-600">{{ $package['summary'] }}</p>

                <form method="POST" action="{{ route('employer.packages.buy') }}" class="mt-auto pt-4">
                    @csrf
                    <input type="hidden" name="tier" value="{{ $tier }}">
                    <div class="grid grid-cols-[1fr_72px] gap-2">
                        <label class="text-xs text-slate-600">Thời gian quảng bá
                            <select name="days" data-days class="form-input mt-1 py-2">
                                @foreach ($package['prices'] as $d => $price)
                                    <option value="{{ $d }}" data-price="{{ $money($price) }}" @selected($d === $days)>{{ $d }} ngày</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-xs text-slate-600">Số lượng
                            <input type="number" name="quantity" value="1" min="1" max="20" class="form-input mt-1 py-2">
                        </label>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <button formaction="{{ route('employer.packages.cart.store') }}" class="rounded-lg border border-teal-700 py-2.5 text-sm font-semibold text-teal-800 hover:bg-teal-50">Thêm vào giỏ</button>
                        <button class="rounded-lg bg-teal-700 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Mua ngay</button>
                    </div>
                </form>

                @include('employer.packages._benefits', ['level' => $package['priority'] + 1, 'rank' => $package['rank'], 'has' => $package['features']])
            </article>
        @endforeach
    </div>

    {{-- Comparison --}}
    <section id="so-sanh" class="mt-5 scroll-mt-24 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <h2 class="px-5 pt-4 pb-3 text-lg font-bold text-slate-900">So sánh quyền lợi từng gói</h2>
        <table class="w-full min-w-[640px] text-sm">
            <thead class="border-y border-slate-200 bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-5 py-2.5 text-left font-semibold">Quyền lợi</th>
                    <th class="px-3 py-2.5 font-semibold">Free</th>
                    @foreach ($packages as $package) <th class="px-3 py-2.5 font-semibold">{{ $package['name'] }}</th> @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-center">
                @foreach ($features as $key => $label)
                    <tr>
                        <td class="px-5 py-2.5 text-left text-slate-700">{{ $label }}</td>
                        <td class="text-slate-300">—</td>
                        @foreach ($packages as $package)
                            <td>@if (in_array($key, $package['features'], true))<span class="text-teal-600">✓</span>@else<span class="text-slate-300">—</span>@endif</td>
                        @endforeach
                    </tr>
                @endforeach
                <tr>
                    <td class="px-5 py-2.5 text-left text-slate-700">Giá 14 ngày / tin</td>
                    <td class="font-semibold text-slate-900">0 đ</td>
                    @foreach ($packages as $package) <td class="font-semibold text-slate-900">{{ $money($package['prices'][14] ?? 0) }}</td> @endforeach
                </tr>
            </tbody>
        </table>
    </section>

    <script>
        document.querySelectorAll('[data-plan]').forEach((plan) => {
            const select = plan.querySelector('[data-days]');
            select.addEventListener('change', () => {
                plan.querySelector('[data-price]').textContent = select.selectedOptions[0].dataset.price;
                plan.querySelector('[data-days-label]').textContent = select.value;
            });
        });
    </script>
@endsection
