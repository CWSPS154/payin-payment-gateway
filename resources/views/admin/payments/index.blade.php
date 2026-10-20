@extends('layouts.app')

@section('title', 'Payments')

@section('content')
    <div class="dashboard-shell">
    <section class="panel dashboard-heading">
        <div><p class="eyebrow">Admin dashboard</p><h1>Payments</h1><p class="muted">Review payment activity and settlement status.</p></div>
    </section>

    <section class="panel">
        <form class="filters" method="GET" action="{{ route('admin.payments.index') }}">
            <label>Created date<input type="date" name="date" value="{{ request('date') }}"></label>
            <label>Merchant
                <select name="merchant_id">
                    <option value="">All merchants</option>
                    @foreach ($merchants as $merchant)
                        <option value="{{ $merchant->id }}" @selected((string) request('merchant_id') === (string) $merchant->id)>{{ $merchant->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (['PENDING', 'SUCCESS', 'FAILED'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>
            <label>Order ID<input name="order_id" value="{{ request('order_id') }}" placeholder="Search order ID"></label>
            <div class="filter-actions">
                <button type="submit">Apply filters</button>
                <a href="{{ route('admin.payments.index') }}">Clear filters</a>
            </div>
        </form>
    </section>

    <section class="panel table-wrap">
        <table>
            <thead><tr><th>Merchant</th><th>Order ID</th><th>Amount</th><th>Gateway</th><th>Status</th><th>Failure reason</th><th>Created at</th></tr></thead>
            <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->merchant->name }}</td>
                    <td><a href="{{ route('admin.payments.show', $payment) }}">{{ $payment->order_id }}</a></td>
                    <td>₹{{ $payment->amount }}</td>
                    <td>{{ $payment->gateway }}</td>
                    <td><span class="badge {{ $payment->status }}">{{ $payment->status }}</span></td>
                    <td>{{ $payment->failure_reason ?: '—' }}</td>
                    <td>{{ $payment->created_at->format('d M Y, H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No payments match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $payments->links() }}</div>
    </section>
    </div>
@endsection
