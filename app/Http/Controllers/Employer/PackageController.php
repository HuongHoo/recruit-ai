<?php

namespace App\Http\Controllers\Employer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PurchasedPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Promotion packages: pricing, session cart, orders (VietQR transfer) and activation on jobs.
 */
class PackageController extends Controller
{
    private const CART = 'package_cart';

    public function index(Request $request): View
    {
        return view('employer.packages.index', [
            'packages' => config('recruitment.packages'),
            'features' => config('recruitment.package_features'),
            'cart' => $this->cartLines($request),
        ]);
    }

    public function cart(Request $request): View
    {
        return view('employer.packages.cart', ['cart' => $this->cartLines($request)]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $item = $this->validItem($request);

        $cart = $request->session()->get(self::CART, []);
        $key = $item['tier'].'-'.$item['days'];
        $cart[$key] = ['tier' => $item['tier'], 'days' => $item['days'], 'quantity' => min(20, ($cart[$key]['quantity'] ?? 0) + $item['quantity'])];
        $request->session()->put(self::CART, $cart);

        return back()->with('status', 'Đã thêm gói '.config("recruitment.packages.{$item['tier']}.name").' vào giỏ.');
    }

    public function removeFromCart(Request $request, string $key): RedirectResponse
    {
        $request->session()->forget(self::CART.'.'.$key);

        return back()->with('status', 'Đã xóa gói khỏi giỏ.');
    }

    /** "Mua ngay": order a single line without touching the cart. */
    public function buy(Request $request): RedirectResponse
    {
        $order = Order::place($request->user(), [$this->validItem($request)]);

        return redirect()->route('employer.orders.show', $order);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $cart = array_values($request->session()->get(self::CART, []));
        if ($cart === []) {
            return redirect()->route('employer.packages.index')->withErrors(['cart' => 'Giỏ hàng đang trống.']);
        }

        $order = Order::place($request->user(), $cart);
        $request->session()->forget(self::CART);

        return redirect()->route('employer.orders.show', $order);
    }

    public function order(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('employer.packages.order', ['order' => $order->load('packages'), 'bank' => config('recruitment.payment')]);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->status === OrderStatus::Pending, 422);

        $order->update(['status' => OrderStatus::Cancelled]);

        return redirect()->route('employer.purchased.index')->with('status', "Đã hủy đơn {$order->code}.");
    }

    public function purchased(Request $request): View
    {
        $user = $request->user();
        $packages = PurchasedPackage::query()->where('user_id', $user->id)->with(['order', 'jobPost']);

        return view('employer.packages.purchased', [
            'unused' => (clone $packages)->unused()->oldest()->get(),
            'running' => (clone $packages)->running()->orderBy('expires_at')->get(),
            'orders' => $user->orders()->withCount('packages')->latest()->get(),
            'jobs' => $user->jobPosts()->visible()->latest()->get(['id', 'title', 'promotion_tier', 'promoted_until']),
            'cartCount' => collect($request->session()->get(self::CART, []))->sum('quantity'),
        ]);
    }

    public function activate(Request $request, PurchasedPackage $package): RedirectResponse
    {
        $user = $request->user();
        abort_unless($package->user_id === $user->id, 404);
        abort_if($package->activated_at !== null || $package->order->status !== OrderStatus::Paid, 422);

        $validated = $request->validate([
            'job_post_id' => ['required', 'integer', Rule::exists('job_posts', 'id')->where('user_id', $user->id)],
        ]);
        $job = $user->jobPosts()->visible()->find($validated['job_post_id']);
        if ($job === null) {
            return back()->withErrors(['job_post_id' => 'Chỉ gắn gói cho tin đang hiển thị.']);
        }

        $package->activateOn($job);

        return back()->with('status', "Đã kích hoạt gói {$package->name()} cho tin \"{$job->title}\" đến {$job->promoted_until->format('d/m/Y H:i')}.");
    }

    /**
     * @return array{tier: string, days: int, quantity: int}
     */
    private function validItem(Request $request): array
    {
        $validated = $request->validate([
            'tier' => ['required', Rule::in(array_keys(config('recruitment.packages')))],
            'days' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        abort_unless(Order::price($validated['tier'], (int) $validated['days']) > 0, 422);

        return ['tier' => $validated['tier'], 'days' => (int) $validated['days'], 'quantity' => (int) $validated['quantity']];
    }

    /**
     * @return Collection<string, array{tier: string, days: int, quantity: int, name: string, price: int, subtotal: int}>
     */
    private function cartLines(Request $request)
    {
        return collect($request->session()->get(self::CART, []))->map(fn (array $line) => $line + [
            'name' => config("recruitment.packages.{$line['tier']}.name"),
            'price' => Order::price($line['tier'], $line['days']),
            'subtotal' => Order::price($line['tier'], $line['days']) * $line['quantity'],
        ]);
    }
}
