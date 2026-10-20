<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'max:100'],
            'merchant_id' => ['nullable', 'integer', 'exists:merchants,id'],
            'status' => ['required', 'in:SUCCESS,FAILED'],
            'reference' => ['nullable', 'string', 'max:255'],
            'failure_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $expiredBeforeWebhook = false;
        $payment = DB::transaction(function () use ($validated, &$expiredBeforeWebhook): Payment {
            $query = Payment::query()->where('order_id', $validated['order_id']);
            if (isset($validated['merchant_id'])) {
                $query->where('merchant_id', $validated['merchant_id']);
            }

            $matches = $query->lockForUpdate()->limit(2)->get();
            abort_if($matches->isEmpty(), 404, 'Payment not found.');
            abort_if($matches->count() > 1, 422, 'Provide a merchant_id for this order ID.');

            $payment = $matches->first();
            $incomingStatus = $validated['status'];

            if ($payment->status === Payment::STATUS_PENDING && $payment->expires_at && $payment->expires_at->isPast()) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'failure_reason' => 'Webhook timed out: no webhook was received before the payment expired.',
                ]);
                $expiredBeforeWebhook = true;

                return $payment->refresh();
            }

            if ($payment->status === $incomingStatus) {
                return $payment;
            }

            abort_if($payment->status !== Payment::STATUS_PENDING, 409, 'Payment already has a different final status.');

            if ($incomingStatus === Payment::STATUS_FAILED) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'reference' => $validated['reference'] ?? null,
                    'failure_reason' => $validated['failure_reason'] ?? 'Payment failed at gateway.',
                ]);

                return $payment->refresh();
            }

            $wallet = Wallet::query()
                ->where('merchant_id', $payment->merchant_id)
                ->lockForUpdate()
                ->firstOrFail();

            $beforeCents = $this->toCents($wallet->balance);
            $amountCents = $this->toCents($payment->amount);
            $afterCents = $beforeCents + $amountCents;
            $before = $this->fromCents($beforeCents);
            $after = $this->fromCents($afterCents);

            $wallet->update(['balance' => $after]);
            WalletTransaction::create([
                'merchant_id' => $payment->merchant_id,
                'payment_id' => $payment->id,
                'type' => 'CREDIT',
                'amount' => $payment->amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference' => $validated['reference'] ?? null,
            ]);
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'reference' => $validated['reference'] ?? null,
                'failure_reason' => null,
            ]);

            return $payment->refresh();
        }, 3);

        abort_if($expiredBeforeWebhook, 409, 'Payment expired before this webhook arrived.');

        return response()->json(['data' => $payment]);
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) substr($fraction, 0, 2);
    }

    private function fromCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
