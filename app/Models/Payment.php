<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_SUCCESS = 'SUCCESS';

    public const STATUS_FAILED = 'FAILED';

    protected $fillable = ['merchant_id', 'order_id', 'amount', 'gateway', 'status', 'reference', 'expires_at', 'failure_reason'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if ($payment->status === self::STATUS_PENDING && $payment->expires_at === null) {
                $payment->expires_at = now()->addMinutes(config('demo.webhook_timeout_minutes', 15));
            }
        });
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function walletTransaction(): HasOne
    {
        return $this->hasOne(WalletTransaction::class);
    }
}
