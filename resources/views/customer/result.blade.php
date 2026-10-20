@extends('layouts.app')
@section('content')
<section class="panel"><p class="eyebrow">Payment result</p>
@if ($payment->status === \App\Models\Payment::STATUS_SUCCESS)<h1>Payment successful</h1><p>The webhook handler was triggered and the merchant wallet was credited.</p>
@elseif ($payment->status === \App\Models\Payment::STATUS_FAILED)<h1>Payment failed</h1><p>{{ $payment->failure_reason ?: 'No wallet credit was made.' }}</p>
@else<h1>Payment status: {{ $payment->status }}</h1>@endif
<p>Order ID: {{ $payment->order_id }}</p><a href="{{ route('customer.offers') }}">Return to offers</a></section>
@endsection
