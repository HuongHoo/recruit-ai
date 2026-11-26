<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * One promotion package unit. Usable once its order is paid; attaching it to a job starts the countdown.
 *
 * @property Carbon|null $activated_at
 * @property Carbon|null $expires_at
 */
class PurchasedPackage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'price' => 'integer',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<JobPost, $this>
     */
    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }

    /** Paid and not yet attached to a job. */
    #[Scope]
    protected function unused(Builder $query): void
    {
        $query->whereNull('activated_at')->whereRelation('order', 'status', OrderStatus::Paid);
    }

    #[Scope]
    protected function running(Builder $query): void
    {
        $query->whereNotNull('activated_at')->where('expires_at', '>', now());
    }

    public function name(): string
    {
        return config("recruitment.packages.{$this->tier}.name", $this->tier);
    }

    /**
     * Attaches the package to a job. A job already promoted keeps the better tier and gets the days added.
     */
    public function activateOn(JobPost $job): void
    {
        DB::transaction(function () use ($job) {
            $priority = (int) config("recruitment.packages.{$this->tier}.priority");
            $start = $job->promoted_until?->isFuture() ? $job->promoted_until : now();
            $expires = $start->copy()->addDays($this->days);

            $this->forceFill(['job_post_id' => $job->id, 'activated_at' => now(), 'expires_at' => $expires])->save();

            $keepCurrent = $job->promoted_until?->isFuture() && $job->promotion_priority > $priority;
            $job->forceFill([
                'promotion_tier' => $keepCurrent ? $job->promotion_tier : $this->tier,
                'promotion_priority' => $keepCurrent ? $job->promotion_priority : $priority,
                'promoted_until' => $expires,
            ])->save();
        });
    }
}
