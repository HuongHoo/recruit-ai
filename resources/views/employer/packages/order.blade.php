@extends('layouts.app')
@section('title', 'Đơn hàng '.$order->code)
@section('subtitle', 'Chuyển khoản theo thông tin dưới đây để hoàn tất thanh toán.')
@section('icon', 'receipt')

@php
    use App\Enums\OrderStatus;
    $money = fn (int $v) => number_format($v, 0, ',', '.').' đ';
    $lines = $order->packages->groupBy(fn ($p) => $p->tier.'-'.$p->days);
@endphp

@section('content')
    <a href="{{ route('employer.purchased.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-slate-800">&larr; Gói đã mua</a>

    <div class="grid gap-4 lg:grid-cols-[1fr_360px]">
        <section class="rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Đơn {{ $order->code }}</h2>
                    <p class="text-xs text-slate-500">Tạo lúc {{ $order->created_at->format('H:i d/m/Y') }}</p>
                </div>
                <span class="{{ $order->status->badgeClasses() }} rounded-full px-3 py-1 text-xs font-semibold">{{ $order->status->label() }}</span>
            </div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    @foreach ($lines as $items)
                        @php $first = $items->first(); @endphp
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">Gói {{ $first->name() }} · {{ $first->days }} ngày</td>
                            <td class="px-3 py-3 text-slate-500">{{ $money($first->price) }} × {{ $items->count() }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ $money($items->sum('price')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200 bg-slate-50">
                    <tr><td colspan="2" class="px-5 py-3 font-semibold">Tổng cộng</td><td class="px-5 py-3 text-right text-lg font-bold text-teal-700">{{ $money($order->total) }}</td></tr>
                </tfoot>
            </table>
            @if ($order->status === OrderStatus::Pending)
                <form method="POST" action="{{ route('employer.orders.cancel', $order) }}" class="border-t border-slate-100 px-5 py-3 text-right" onsubmit="return confirm('Hủy đơn hàng này?')">
                    @csrf @method('PATCH')
                    <button class="text-sm font-medium text-red-600 hover:underline">Hủy đơn</button>
                </form>
            @endif
        </section>

        <aside class="self-start rounded-2xl border border-slate-200 bg-white p-5">
            @if ($order->status === OrderStatus::Pending)
                <h2 class="font-bold text-slate-900">Quét mã VietQR để thanh toán</h2>
                @if ($bank['is_demo'])
                    <p class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700">⚠️ Đây là tài khoản demo, <strong>không chuyển tiền thật</strong>. Quản trị hệ thống cần cấu hình tài khoản nhận tiền trong file .env.</p>
                @endif
                <img src="{{ $order->qrUrl() }}" alt="Mã VietQR đơn {{ $order->code }}" class="mx-auto mt-4 w-60 rounded-xl border border-slate-200">
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Ngân hàng</dt><dd class="font-medium">{{ $bank['bank_id'] }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Số tài khoản</dt><dd class="font-medium">{{ $bank['account_no'] }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Chủ tài khoản</dt><dd class="text-right font-medium">{{ $bank['account_name'] }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Số tiền</dt><dd class="font-bold text-teal-700">{{ $money($order->total) }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Nội dung CK</dt><dd class="rounded bg-amber-50 px-2 font-mono font-bold text-amber-800">{{ $order->code }}</dd></div>
                </dl>
                <p class="mt-4 rounded-lg bg-teal-50 px-3 py-2 text-xs text-teal-800">Sau khi nhận được chuyển khoản, Quản trị viên sẽ xác nhận đơn và gói sẽ xuất hiện trong <strong>Gói đã mua</strong>.</p>
            @elseif ($order->status === OrderStatus::Paid)
                <p class="text-4xl">✅</p>
                <h2 class="mt-2 font-bold text-slate-900">Đã thanh toán</h2>
                <p class="mt-1 text-sm text-slate-500">Xác nhận lúc {{ $order->paid_at->format('H:i d/m/Y') }}. Vào Gói đã mua để gắn gói cho tin tuyển dụng.</p>
                <a href="{{ route('employer.purchased.index') }}" class="mt-4 block rounded-lg bg-teal-700 py-2.5 text-center text-sm font-semibold text-white hover:bg-teal-800">Đến Gói đã mua</a>
            @else
                <h2 class="font-bold text-slate-900">Đơn đã hủy</h2>
                <a href="{{ route('employer.packages.index') }}" class="mt-4 block rounded-lg border border-teal-600 py-2.5 text-center text-sm font-semibold text-teal-700 hover:bg-teal-50">Chọn gói khác</a>
            @endif
        </aside>
    </div>
@endsection
