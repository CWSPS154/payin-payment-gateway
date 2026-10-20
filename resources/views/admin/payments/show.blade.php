@extends('layouts.app')

@section('title', 'Payment '.$payment->order_id)

@section('content')
    <a class="button secondary" href="{{ route('admin.payments.index') }}">← All payments</a>
    <h1 style="margin-top: 22px">Payment details</h1>
    <p class="muted">Order {{ $payment->order_id }}</p>

    <section class="panel">
        <h2>Payment</h2>
        <dl class="details">
            <div><dt>Merchant</dt><dd>{{ $payment->merchant->name }}</dd></div>
            <div><dt>Order ID</dt><dd>{{ $payment->order_id }}</dd></div>
            <div><dt>Amount</dt><dd>₹{{ $payment->amount }}</dd></div>
            <div><dt>Gateway</dt><dd>{{ $payment->gateway }}</dd></div>
            <div><dt>Status</dt><dd><span class="badge {{ $payment->status }}">{{ $payment->status }}</span></dd></div>
            <div><dt>Gateway reference</dt><dd>{{ $payment->reference ?: '—' }}</dd></div>
            <div><dt>Failure reason</dt><dd>{{ $payment->failure_reason ?: '—' }}</dd></div>
            <div><dt>Webhook deadline</dt><dd>{{ $payment->expires_at?->format('d M Y, H:i:s') ?: '—' }}</dd></div>
            <div><dt>Created at</dt><dd>{{ $payment->created_at->format('d M Y, H:i:s') }}</dd></div>
            <div><dt>Current wallet balance</dt><dd>₹{{ $payment->merchant->wallet?->balance ?? '0.00' }}</dd></div>
        </dl>
    </section>

    <section class="panel">
        <h2>Wallet transaction</h2>
        @if ($payment->walletTransaction)
            <dl class="details">
                <div><dt>Type</dt><dd>{{ $payment->walletTransaction->type }}</dd></div>
                <div><dt>Amount</dt><dd>₹{{ $payment->walletTransaction->amount }}</dd></div>
                <div><dt>Balance before</dt><dd>₹{{ $payment->walletTransaction->balance_before }}</dd></div>
                <div><dt>Balance after</dt><dd>₹{{ $payment->walletTransaction->balance_after }}</dd></div>
                <div><dt>Reference</dt><dd>{{ $payment->walletTransaction->reference ?: '—' }}</dd></div>
            </dl>
        @else
            <p class="muted">No wallet transaction has been recorded for this payment.</p>
        @endif
    </section>
@endsection
