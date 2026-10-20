<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'merchant_id' => ['nullable', 'integer', 'exists:merchants,id'],
            'status' => ['nullable', Rule::in(['PENDING', 'SUCCESS', 'FAILED'])],
            'order_id' => ['nullable', 'string', 'max:100'],
        ]);

        $payments = Payment::query()
            ->with('merchant')
            ->when($request->filled('merchant_id'), fn ($query) => $query->where('merchant_id', $request->input('merchant_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('order_id'), fn ($query) => $query->where('order_id', 'like', '%'.$request->input('order_id').'%'))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->input('date')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'merchants' => Merchant::orderBy('name')->get(),
        ]);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['merchant.wallet', 'walletTransaction']);

        return view('admin.payments.show', compact('payment'));
    }
}
