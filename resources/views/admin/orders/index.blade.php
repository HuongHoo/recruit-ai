@extends('layouts.app')
@section('title', 'Đơn mua gói dịch vụ')

@php
    use App\Enums\OrderStatus;
    $money = fn (int $v) => number_format($v, 0, ',', '.').' đ';
@endphp

@section('content')
    <nav class="mb-4 flex gap-2">
        @foreach (OrderStatus::cases() as $case)
            <a href="{{ route('admin.orders.index', ['status' => $case->value]) }}" @class(['rounded-lg px-3 py-1.5 text-sm font-medium', 'bg-slate-900 text-white' => $status === $case->value, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $status !== $case->value])>{{ $case->label() }}</a>
        @endforeach
    </nav>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        @if ($orders->isEmpty())
            <p class="px-6 py-12 text-center text-sm text-slate-500">Không có đơn nào.</p>
        @else
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr><th class="px-5 py-3">Mã đơn / Nội dung CK</th><th class="px-5 py-3">Nhà tuyển dụng</th><th class="px-5 py-3">Số gói</th><th class="px-5 py-3 text-right">Số tiền</th><th class="px-5 py-3">Thời gian</th><th class="px-5 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-5 py-3 font-mono font-semibold text-slate-900">{{ $order->code }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $order->user->company->name ?? $order->user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->user->email }}</p>
                            </td>
                            <td class="px-5 py-3">{{ $order->packages_count }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ $money($order->total) }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ ($order->paid_at ?? $order->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                @if ($order->status === OrderStatus::Pending)
                                    <form method="POST" action="{{ route('admin.orders.confirm', $order) }}" onsubmit="return confirm('Xác nhận đã nhận {{ $money($order->total) }} cho đơn {{ $order->code }}?')">
                                        @csrf @method('PATCH')
                                        <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Xác nhận đã thanh toán</button>
                                    </form>
                                @else
                                    <span class="{{ $order->status->badgeClasses() }} rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $order->status->label() }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
@endsection
