@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
<div class="landing-page">
    <section class="landing-hero">
        <div class="hero-copy">
            <p class="hero-kicker">A payment flow you can explore</p>
            <h1>From a simple order to a settled payment.</h1>
            <p>See how a merchant offer becomes a customer payment and a wallet update. Choose a role below to explore each part.</p>
        </div>
        <div class="flow-preview" aria-label="Example payment flow">
            <div class="flow-topline"><span>Demo transaction</span><span class="flow-status"><i></i> Ready to explore</span></div>
            <div class="flow-step">
                <span class="flow-icon buyer-icon">B</span>
                <span class="flow-label"><strong>Customer order</strong><small>Demo Item · ABC Store</small></span>
                <strong class="flow-amount">₹1,000</strong>
            </div>
            <div class="flow-connector"><span></span></div>
            <div class="flow-step">
                <span class="flow-icon wallet-icon">W</span>
                <span class="flow-label"><strong>Merchant wallet</strong><small>Updated after payment</small></span>
                <strong class="flow-amount credit">+₹1,000</strong>
            </div>
            <div class="flow-footer">One flow · Three ways to explore</div>
        </div>
    </section>

    <section class="landing-lower" aria-label="Choose how to explore">
        <div class="account-paths">
            @auth
                @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                    <p class="section-kicker">Your workspace</p>
                    <h2>Welcome back, {{ auth()->user()->name }}</h2>
                    <p class="muted">Your admin session is active. Continue to review payments and wallet settlements.</p>
                    <a class="text-action" href="{{ route('admin.payments.index') }}">Open Admin Dashboard <span aria-hidden="true">↗</span></a>
                @else
                    <p class="section-kicker">Your workspace</p>
                    <h2>Welcome back, {{ auth()->user()->name }}</h2>
                    <p class="muted">Your merchant session is active. Update your offer and review your payment activity.</p>
                    <a class="text-action" href="{{ route('merchant.dashboard') }}">Open Merchant Dashboard <span aria-hidden="true">↗</span></a>
                @endif
            @else
                <p class="section-kicker">For your account</p>
                <h2>Manage the payment flow</h2>
                <a class="account-link" href="{{ route('admin.login') }}"><span class="account-link-mark admin-mark">A</span><span><strong>Admin</strong><small>Review every merchant payment</small></span><span class="link-arrow">↗</span></a>
                <a class="account-link" href="{{ route('merchant.login') }}"><span class="account-link-mark merchant-mark">M</span><span><strong>Merchant</strong><small>Manage your offer and wallet</small></span><span class="link-arrow">↗</span></a>
                <p class="register-line">New to the demo? <a href="{{ route('merchant.register') }}">Register as Merchant</a></p>
            @endauth
        </div>

        <a class="customer-path" href="{{ route('customer.offers') }}">
            <span class="customer-path-kicker">No account needed</span>
            <h2>Try the customer checkout</h2>
            <p>Choose a live demo offer, get an automatic order ID, and simulate a payment.</p>
            <span class="customer-path-action">Pay as Customer <span aria-hidden="true">→</span></span>
        </a>
    </section>
</div>
@endsection
