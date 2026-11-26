@extends('layouts.app')
@section('title', 'Giỏ hàng')
@section('subtitle', 'Gộp nhiều gói và thanh toán một lần qua VietQR.')
@section('icon', 'receipt')

@php $money = fn (int $v) => number_format($v, 0, ',', '.').' đ'; @endphp

@section('content')
    <a href="{{ route('employer.packages.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-slate-800">&larr; Gói dịch vụ</a>

    <div class="grid gap-4 lg:grid-cols-[1fr_320px]">
        <section class="rounded-2xl border border-slate-200 bg-white">
            <h2 class="border-b border-slate-100 px-5 py-4 text-lg font-bold text-slate-900">Gói trong giỏ</h2>
            @forelse ($cart as $key => $line)
                <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-0">
                    <span class="grid size-11 place-items-center rounded-xl bg-teal-50 font-bold text-teal-700">{{ mb_substr($line['name'], 0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900">Gói {{ $line['name'] }} · {{ $line['days'] }} ngày</p>
                        <p class="text-sm text-slate-500">{{ $money($line['price']) }} × {{ $line['quantity'] }} tin</p>
                    </div>
                    <p class="font-bold text-slate-900">{{ $money($line['subtotal']) }}</p>
                    <form method="POST" action="{{ route('employer.packages.cart.destroy', $key) }}">
                        @csrf @method('DELETE')
                        <button class="rounded-lg px-2 py-1 text-sm text-red-600 hover:bg-red-50">Xóa</button>
                    </form>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="font-medium text-slate-700">Giỏ hàng đang trống</p>
                    <a href="{{ route('employer.packages.index') }}" class="mt-3 inline-block rounded-lg border border-teal-600 px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">Chọn gói dịch vụ</a>
                </div>
            @endforelse
        </section>

        <aside class="self-start rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="font-bold text-slate-900">Tóm tắt đơn hàng</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Số gói</dt><dd class="font-medium">{{ $cart->sum('quantity') }}</dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-2 text-base"><dt class="font-semibold">Tổng cộng</dt><dd class="font-bold text-teal-700">{{ $money($cart->sum('subtotal')) }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('employer.packages.checkout') }}" class="mt-4">
                @csrf
                <button @disabled($cart->isEmpty()) class="w-full rounded-lg bg-teal-700 py-2.5 text-sm font-semibold text-white hover:bg-teal-800 disabled:opacity-40">Đặt hàng & thanh toán</button>
            </form>
            <p class="mt-3 text-xs text-slate-500">Gói chỉ được dùng sau khi thanh toán được xác nhận.</p>
        </aside>
    </div>
@endsection
