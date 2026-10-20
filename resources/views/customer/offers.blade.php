@extends('layouts.app')
@section('content')
<section class="panel"><p class="eyebrow">Customer checkout</p><h1>Choose an offer</h1><p>Select Buy Now to create a valid order ID and continue to simulated checkout.</p>
<div class="table-wrap"><table><thead><tr><th>Merchant</th><th>Offer</th><th>Amount</th><th></th></tr></thead><tbody>
@forelse ($merchants as $merchant)<tr><td>{{ $merchant->name }}</td><td>{{ $merchant->offer_name }}</td><td>₹{{ number_format((float) $merchant->offer_amount, 2) }}</td><td><form method="POST" action="{{ route('customer.checkout.buy', $merchant) }}">@csrf<button type="submit">Buy Now</button></form></td></tr>
@empty<tr><td colspan="4">No offers are available right now.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
