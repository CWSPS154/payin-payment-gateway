@extends('layouts.app')
@section('content')
<div class="dashboard-shell">
<section class="panel dashboard-heading">
    <div><p class="eyebrow">Merchant dashboard</p><h1>{{ $merchant->name }}</h1><p class="muted">Your wallet, offer, and payment activity.</p></div>
</section>
<section class="merchant-stats" aria-label="Merchant payment summary">
    <article class="stat-card wallet-stat"><span>Wallet balance</span><strong>₹{{ number_format((float) ($merchant->wallet->balance ?? 0), 2) }}</strong><small>Available balance</small></article>
    <article class="stat-card"><span>Total payments</span><strong>{{ $totalPayments }}</strong><small>All time</small></article>
    <article class="stat-card"><span>Successful</span><strong>{{ $successfulPayments }}</strong><small>Settled payments</small></article>
    <article class="stat-card"><span>Pending</span><strong>{{ $pendingPayments }}</strong><small>Awaiting result</small></article>
    <article class="stat-card"><span>Failed</span><strong>{{ $failedPayments }}</strong><small>Includes expired webhooks</small></article>
</section>
<section class="panel offer-section">
    <div class="section-heading"><div><p class="eyebrow">Store listing</p><h2>Your customer offer</h2><p class="muted">This is the price and offer customers see when they visit the payment page.</p></div>
        @if ($offerIsVisible)<span class="badge SUCCESS">Visible to customers</span>@else<span class="badge">Not published</span>@endif
    </div>
    @if (session('status')) <p class="success">{{ session('status') }}</p> @endif
    <form method="POST" action="{{ route('merchant.offer.update') }}" class="offer-form">@csrf
        <div class="offer-field"><label>Offer name<input name="offer_name" value="{{ old('offer_name', $merchant->offer_name) }}" maxlength="255" required></label>@error('offer_name') <p class="error">{{ $message }}</p> @enderror</div>
        <div class="offer-field"><label>Price (₹)<input name="offer_amount" type="number" min="0.01" step="0.01" value="{{ old('offer_amount', $merchant->offer_amount) }}" required></label>@error('offer_amount') <p class="error">{{ $message }}</p> @enderror</div>
        <button type="submit">Save offer</button>
    </form>
    <p class="offer-note">Changes appear on the <a href="{{ route('customer.offers') }}">customer payment page</a> as soon as you save.</p>
</section>
<section class="panel payment-activity">
    <div class="section-heading"><div><p class="eyebrow">Activity</p><h2>Payments</h2><p class="muted">Search and review payments processed for your store.</p></div></div>
    <form class="filters merchant-filters" method="GET" action="{{ route('merchant.dashboard') }}">
        <label>Created date<input type="date" name="date" value="{{ request('date') }}"></label>
        <label>Status<select name="status"><option value="">All statuses</option>@foreach (['PENDING', 'SUCCESS', 'FAILED'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></label>
        <label>Order ID<input name="order_id" value="{{ request('order_id') }}" maxlength="100" placeholder="Search order ID"></label>
        <div class="filter-actions"><button type="submit">Apply filters</button><a href="{{ route('merchant.dashboard') }}">Clear filters</a></div>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Order ID</th><th>Amount</th><th>Gateway</th><th>Status</th><th>Reference</th><th>Wallet credit</th><th>Created</th></tr></thead><tbody>
    @forelse ($payments as $payment)
        <tr><td>{{ $payment->order_id }}</td><td>₹{{ number_format((float) $payment->amount, 2) }}</td><td>{{ $payment->gateway }}</td><td><span class="badge {{ $payment->status }}">{{ $payment->status }}</span>@if($payment->failure_reason)<small class="balance-note">{{ $payment->failure_reason }}</small>@endif</td><td>{{ $payment->reference ?: '—' }}</td><td>@if($payment->walletTransaction) ₹{{ number_format((float) $payment->walletTransaction->amount, 2) }}<small class="balance-note">Balance after: ₹{{ number_format((float) $payment->walletTransaction->balance_after, 2) }}</small>@else — @endif</td><td>{{ $payment->created_at->format('d M Y, H:i') }}</td></tr>
    @empty<tr><td colspan="7">No payments match these filters.</td></tr>@endforelse
    </tbody></table></div>
    <div class="pagination">{{ $payments->links() }}</div>
</section>
</div>
@endsection
