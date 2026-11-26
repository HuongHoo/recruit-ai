<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A package purchase paid by bank transfer and confirmed by an admin.
 *
 * @property OrderStatus $status
 * @property Carbon|null $paid_at
 */
class Order extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Creates a pending order with one purchased package per unit.
     *
     * @param  array<int, array{tier: string, days: int, quantity: int}>  $items
     */
    public static function place(User $user, array $items): self
    {
        return DB::transaction(function () use ($user, $items) {
            $order = self::query()->create([
                'user_id' => $user->id,
                'code' => 'DH'.now()->format('ymd').Str::upper(Str::random(5)),
                'total' => collect($items)->sum(fn ($i) => self::price($i['tier'], $i['days']) * $i['quantity']),
            ]);

            foreach ($items as $item) {
                for ($n = 0; $n < $item['quantity']; $n++) {
                    $order->packages()->create([
                        'user_id' => $user->id,
                        'tier' => $item['tier'],
                        'days' => $item['days'],
                        'price' => self::price($item['tier'], $item['days']),
                    ]);
                }
            }

            return $order;
        });
    }

    public static function price(string $tier, int $days): int
    {
        return (int) config("recruitment.packages.{$tier}.prices.{$days}");
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PurchasedPackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(PurchasedPackage::class);
    }

    public function markPaid(User $admin): void
    {
        $this->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now(), 'confirmed_by' => $admin->id])->save();
    }

    /** VietQR image URL prefilled with amount and transfer note. */
    public function qrUrl(): string
    {
        $bank = config('recruitment.payment');

        return 'https://img.vietqr.io/image/'.rawurlencode($bank['bank_id']).'-'.rawurlencode($bank['account_no']).'-compact2.png?'
            .http_build_query(['amount' => $this->total, 'addInfo' => $this->code, 'accountName' => $bank['account_name']]);
    }
}
