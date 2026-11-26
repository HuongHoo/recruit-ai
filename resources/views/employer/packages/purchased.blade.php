@extends('layouts.app')
@section('title', 'Gói đã mua')
@section('subtitle', 'Xem gói còn lại, lượt mua thêm và lịch sử thanh toán.')
@section('icon', 'box')

@php
    use App\Enums\OrderStatus;
    $money = fn (int $v) => number_format($v, 0, ',', '.').' đ';
    $pending = $orders->where('status', OrderStatus::Pending);
@endphp

@section('content')
    {{-- Product tabs --}}
    <div class="grid gap-3 md:grid-cols-3">
        <div class="flex items-center gap-3 rounded-2xl bg-gradient-to-r from-[#0f3b3e] to-teal-700 px-4 py-4 text-white shadow-lg shadow-teal-900/20">
            <span class="grid size-10 place-items-center rounded-xl border border-white/20 bg-white/10">⚡</span>
            <span><span class="block font-semibold">Quảng bá tin tuyển dụng</span><span class="block text-xs text-teal-100">Đưa tin lên đầu, tiếp cận nhiều ứng viên hơn</span></span>
        </div>
        @foreach ([['Quảng bá doanh nghiệp', 'Đưa doanh nghiệp lên trang chủ'], ['Quản lý tuyển dụng', 'AI đọc CV, quản lý ứng viên ở một nơi']] as [$title, $text])
            <div class="flex cursor-not-allowed items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 opacity-70" title="Chức năng sắp ra mắt">
                <span class="grid size-10 place-items-center rounded-xl border border-teal-100 bg-teal-50 text-teal-600">▦</span>
                <span><span class="flex items-center gap-2 font-semibold text-slate-900">{{ $title }} <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">Sắp có</span></span><span class="block text-xs text-slate-500">{{ $text }}</span></span>
            </div>
        @endforeach
    </div>

    {{-- Promo / pending notice --}}
    <section class="mt-4 flex flex-col items-center gap-6 rounded-2xl border border-slate-200 bg-white px-6 py-6 sm:flex-row">
        <span class="text-6xl">📣</span>
        <div class="flex-1">
            @if ($pending->isNotEmpty())
                <h2 class="text-lg font-bold text-slate-900">Bạn có {{ $pending->count() }} đơn chờ thanh toán</h2>
                <p class="mt-1 text-sm text-slate-500">Chuyển khoản theo mã VietQR của đơn để gói được kích hoạt sau khi Quản trị viên xác nhận.</p>
            @elseif ($unused->isEmpty())
                <h2 class="text-lg font-bold text-slate-900">Sẵn sàng cho đợt tuyển dụng tiếp theo</h2>
                <p class="mt-1 text-sm text-slate-500">Bạn chưa có gói quảng bá sẵn để dùng. Chọn gói phù hợp, thanh toán một lần và dùng cho từng tin khi cần.</p>
            @else
                <h2 class="text-lg font-bold text-slate-900">Bạn còn {{ $unused->count() }} gói chưa dùng</h2>
                <p class="mt-1 text-sm text-slate-500">Gắn gói vào tin đang hiển thị để bắt đầu quảng bá. Số ngày chỉ được tính từ lúc kích hoạt.</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employer.packages.index') }}" class="rounded-lg border border-teal-700 px-4 py-2.5 text-sm font-semibold text-teal-800 hover:bg-teal-50">Khám phá gói quảng bá</a>
            <a href="{{ route('employer.packages.cart') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Xem giỏ hàng @if ($cartCount)({{ $cartCount }})@endif</a>
        </div>
    </section>

    @error('job_post_id') <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div> @enderror

    {{-- Unused --}}
    <h2 class="mt-6 flex items-center gap-2 font-semibold text-slate-900">Chưa sử dụng <span class="rounded-md border border-teal-200 bg-teal-50 px-1.5 py-0.5 text-[11px] font-semibold text-teal-700">Số lượng: {{ $unused->count() }}</span></h2>
    <section class="mt-2 rounded-2xl border border-slate-200 bg-white">
        @forelse ($unused as $package)
            <form method="POST" action="{{ route('employer.purchased.activate', $package) }}" class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-3.5 last:border-0">
                @csrf
                <span class="grid size-10 place-items-center rounded-xl bg-teal-50 font-bold text-teal-700">{{ mb_substr($package->name(), 0, 1) }}</span>
                <div class="min-w-40 flex-1">
                    <p class="font-semibold text-slate-900">Gói {{ $package->name() }} · {{ $package->days }} ngày</p>
                    <p class="text-xs text-slate-500">Đơn {{ $package->order->code }} · mua {{ $package->order->paid_at?->format('d/m/Y') }}</p>
                </div>
                @if ($jobs->isEmpty())
                    <a href="{{ route('employer.jobs.create') }}" class="text-sm font-semibold text-teal-700">Đăng tin để dùng gói</a>
                @else
                    <select name="job_post_id" required class="form-input w-64 py-2" aria-label="Chọn tin để gắn gói">
                        <option value="">— Chọn tin tuyển dụng —</option>
                        @foreach ($jobs as $job)
                            <option value="{{ $job->id }}">{{ $job->title }}{{ $job->activePromotion() ? ' (đang chạy '.$job->activePromotion()['name'].')' : '' }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Kích hoạt</button>
                @endif
            </form>
        @empty
            <div class="px-5 py-5">
                <p class="font-semibold text-slate-900">Bạn chưa có gói nào.</p>
                <p class="mt-1 text-xs text-slate-500">Gói đã mua sẽ hiện ở đây.</p>
                <a href="{{ route('employer.packages.index') }}" class="mt-3 inline-block rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Gói dịch vụ</a>
            </div>
        @endforelse
    </section>

    {{-- Running --}}
    <section class="mt-4 rounded-2xl border border-slate-200 bg-white">
        <h2 class="border-b border-slate-100 px-5 py-3.5 font-semibold text-slate-900">Đang sử dụng · {{ $running->count() }}</h2>
        @forelse ($running as $package)
            @php $total = max(1, $package->activated_at->diffInSeconds($package->expires_at)); $left = now()->diffInSeconds($package->expires_at); @endphp
            <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 px-5 py-3.5 last:border-0">
                <div class="min-w-48 flex-1">
                    <p class="font-semibold text-slate-900">Gói {{ $package->name() }} · {{ $package->jobPost?->title ?? 'Tin đã xóa' }}</p>
                    <p class="text-xs text-slate-500">{{ $package->activated_at->format('d/m/Y') }} → {{ $package->expires_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="w-48">
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-teal-500" style="width: {{ round($left / $total * 100) }}%"></div></div>
                    <p class="mt-1 text-right text-xs text-slate-500">Còn {{ $package->expires_at->diffForHumans(null, true) }}</p>
                </div>
            </div>
        @empty
            <p class="px-5 py-4 text-xs text-slate-500">Gói đang dùng sẽ hiện ở đây.</p>
        @endforelse
    </section>

    {{-- History --}}
    <section class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <h2 class="border-b border-slate-100 px-5 py-3.5 font-semibold text-slate-900">Lịch sử thanh toán</h2>
        @if ($orders->isEmpty())
            <p class="px-5 py-4 text-xs text-slate-500">Chưa có đơn thanh toán.</p>
        @else
            <table class="w-full min-w-[560px] text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr><th class="px-5 py-2.5 text-left">Mã đơn</th><th class="px-3 py-2.5 text-left">Ngày tạo</th><th class="px-3 py-2.5 text-left">Số gói</th><th class="px-3 py-2.5 text-right">Tổng tiền</th><th class="px-3 py-2.5">Trạng thái</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-5 py-3 font-mono font-semibold text-slate-900">{{ $order->code }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $order->packages_count }}</td>
                            <td class="px-3 py-3 text-right font-semibold">{{ $money($order->total) }}</td>
                            <td class="px-3 py-3 text-center"><span class="{{ $order->status->badgeClasses() }} rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $order->status->label() }}</span></td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('employer.orders.show', $order) }}" class="font-semibold text-teal-700 hover:underline">{{ $order->status === OrderStatus::Pending ? 'Thanh toán' : 'Chi tiết' }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
