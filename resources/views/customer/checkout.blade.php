@extends('layouts.app')
@section('content')
<section class="panel"><p class="eyebrow">Simulated checkout</p><h1>{{ $payment->merchant->offer_name }}</h1>
<p>Merchant: {{ $payment->merchant->name }}</p><p>Order ID: <strong>{{ $payment->order_id }}</strong></p>
<p>Amount: <strong>₹{{ number_format((float) $payment->amount, 2) }}</strong></p><p>This demo does not charge a real payment method.</p>
<p class="checkout-status">Payment status: <span class="badge {{ $payment->status }}">{{ $payment->status }}</span></p>
@if ($payment->status === \App\Models\Payment::STATUS_PENDING && $payment->expires_at)<p class="muted">Webhook deadline: {{ $payment->expires_at->format('d M Y, H:i:s') }}</p>@endif
@if ($payment->status === \App\Models\Payment::STATUS_FAILED && $payment->failure_reason)<p class="notice">Failure reason: {{ $payment->failure_reason }}</p>@endif
@if (session('webhook_waiting'))<p class="notice">{{ session('webhook_waiting') }}</p>@endif
@if ($payment->status === \App\Models\Payment::STATUS_PENDING)
    @if ($pendingWebhookOutcome)
        <div class="manual-webhook-pending">
            <div><strong>Manual webhook is waiting</strong><p>Simulated outcome: {{ $pendingWebhookOutcome }}. The payment remains pending until you trigger the webhook.</p></div>
            <form method="POST" action="{{ route('customer.checkout.trigger-webhook', $payment) }}">@csrf<button type="submit">Trigger Webhook</button></form>
        </div>
    @else
        <form method="POST" action="{{ route('customer.checkout.simulate', $payment) }}" class="checkout-form">@csrf
            <fieldset class="mode-options"><legend>Webhook mode</legend>
                <label class="mode-option"><input type="radio" name="webhook_mode" value="manual" checked><span><strong>Manual</strong><small>Keep the payment pending until you trigger the webhook.</small></span></label>
                <label class="mode-option"><input type="radio" name="webhook_mode" value="automatic"><span><strong>Automatic</strong><small>Trigger the webhook as soon as you record the outcome.</small></span></label>
            </fieldset>
            <div class="actions"><button name="outcome" value="SUCCESS">Record successful payment</button><button class="secondary" name="outcome" value="FAILED">Record failed payment</button></div>
        </form>
    @endif
@else<p>Payment status: {{ $payment->status }}</p>@endif</section>
@endsection
