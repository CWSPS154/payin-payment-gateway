<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerCheckoutController extends Controller
{
    public function index(): View
    {
        return view('customer.offers', [
            'merchants' => Merchant::whereNotNull('offer_name')->whereNotNull('offer_amount')
                ->where('offer_amount', '>', 0)->orderBy('name')->get(),
        ]);
    }

    public function buy(Merchant $merchant): RedirectResponse
    {
        abort_unless($merchant->offer_name && $merchant->offer_amount > 0, 404);
        do {
            $orderId = 'ORD-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(8));
        } while ($merchant->payments()->where('order_id', $orderId)->exists());

        $payment = $merchant->payments()->create([
            'order_id' => $orderId,
            'amount' => $merchant->offer_amount,
            'gateway' => 'TEST_GATEWAY',
            'status' => Payment::STATUS_PENDING,
        ]);

        return redirect()->route('customer.checkout.show', $payment);
    }

    public function show(Payment $payment): View
    {
        return view('customer.checkout', [
            'payment' => $payment->load('merchant'),
            'pendingWebhookOutcome' => session($this->pendingWebhookKey($payment)),
        ]);
    }

    public function result(Payment $payment): View
    {
        return view('customer.result', ['payment' => $payment]);
    }

    public function simulate(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === Payment::STATUS_PENDING, 409);
        $validated = $request->validate([
            'outcome' => ['required', 'in:SUCCESS,FAILED'],
            'webhook_mode' => ['required', 'in:manual,automatic'],
        ]);

        if ($validated['webhook_mode'] === 'manual') {
            $request->session()->put($this->pendingWebhookKey($payment), $validated['outcome']);

            return redirect()->route('customer.checkout.show', $payment)
                ->with('webhook_waiting', 'The simulated payment is recorded. Its webhook is waiting for your manual trigger.');
        }

        $this->triggerWebhookHandler($payment, $validated['outcome']);

        return redirect()->route('customer.checkout.result', $payment)->with('simulated_outcome', $validated['outcome']);
    }

    public function triggerWebhook(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === Payment::STATUS_PENDING, 409);
        $sessionKey = $this->pendingWebhookKey($payment);
        $outcome = $request->session()->get($sessionKey);
        abort_unless(in_array($outcome, ['SUCCESS', 'FAILED'], true), 409, 'No pending webhook is ready for this payment.');

        $this->triggerWebhookHandler($payment, $outcome);
        $request->session()->forget($sessionKey);

        return redirect()->route('customer.checkout.result', $payment)->with('simulated_outcome', $outcome);
    }

    private function triggerWebhookHandler(Payment $payment, string $outcome): void
    {
        $webhookRequest = Request::create('/api/payment/webhook', 'POST', [
            'merchant_id' => $payment->merchant_id,
            'order_id' => $payment->order_id,
            'status' => $outcome,
            'reference' => 'SIM-'.$payment->order_id,
        ]);
        $response = app(PaymentWebhookController::class)->store($webhookRequest);
        abort_unless($response instanceof JsonResponse && $response->isSuccessful(), 500);
    }

    private function pendingWebhookKey(Payment $payment): string
    {
        return 'pending_simulated_webhooks.'.$payment->id;
    }
}
