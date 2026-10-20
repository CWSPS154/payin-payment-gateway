<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MerchantDashboardController extends Controller
{
    public function show(Request $request): View
    {
        $merchant = $request->user()->merchant;
        abort_unless($merchant, 404);

        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in([Payment::STATUS_PENDING, Payment::STATUS_SUCCESS, Payment::STATUS_FAILED])],
            'order_id' => ['nullable', 'string', 'max:100'],
        ]);

        $paymentsQuery = $merchant->payments()->with('walletTransaction')
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $filters['date']))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $filters['status']))
            ->when($request->filled('order_id'), fn ($query) => $query->where('order_id', 'like', '%'.$filters['order_id'].'%'));

        return view('merchant.dashboard', [
            'merchant' => $merchant->load('wallet'),
            'payments' => $paymentsQuery->latest()->paginate(15)->withQueryString(),
            'totalPayments' => $merchant->payments()->count(),
            'successfulPayments' => $merchant->payments()->where('status', Payment::STATUS_SUCCESS)->count(),
            'pendingPayments' => $merchant->payments()->where('status', Payment::STATUS_PENDING)->count(),
            'failedPayments' => $merchant->payments()->where('status', Payment::STATUS_FAILED)->count(),
            'offerIsVisible' => filled($merchant->offer_name) && (float) $merchant->offer_amount > 0,
        ]);
    }

    public function updateOffer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'offer_name' => ['required', 'string', 'max:255'],
            'offer_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999.99'],
        ]);

        $merchant = $request->user()->merchant;
        abort_unless($merchant, 404);
        $merchant->update($validated);

        return redirect()->route('merchant.dashboard')->with('status', 'Offer updated.');
    }
}
