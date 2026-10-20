<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Payment;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payments:expire-pending', function (): void {
    $expired = Payment::query()
        ->where('status', Payment::STATUS_PENDING)
        ->whereNotNull('expires_at')
        ->where('expires_at', '<=', now())
        ->update([
            'status' => Payment::STATUS_FAILED,
            'failure_reason' => 'Webhook timed out: no webhook was received before the payment expired.',
            'updated_at' => now(),
        ]);

    $this->info("Expired {$expired} pending payment(s).");
})->purpose('Mark pending payments as failed when their webhook deadline passes');

Schedule::command('payments:expire-pending')->everyMinute()->withoutOverlapping();
