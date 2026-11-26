<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin confirms package orders once the bank transfer has arrived.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['status' => ['nullable', Rule::enum(OrderStatus::class)]]);
        $status = $request->query('status', OrderStatus::Pending->value);

        return view('admin.orders.index', [
            'orders' => Order::query()->with('user.company')->withCount('packages')
                ->where('status', $status)->latest()->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function confirm(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->status === OrderStatus::Pending, 422);
        $order->markPaid($request->user());

        return back()->with('status', "Đã xác nhận thanh toán đơn {$order->code}.");
    }
}
