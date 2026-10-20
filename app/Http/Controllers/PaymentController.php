<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchant_id' => ['required', 'integer', 'exists:merchants,id'],
            'order_id' => [
                'required', 'string', 'max:100',
                Rule::unique('payments')->where('merchant_id', $request->input('merchant_id')),
            ],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99', 'decimal:0,2'],
            'gateway' => ['required', 'string', 'max:100'],
        ]);

        try {
            $payment = Payment::create($validated + ['status' => Payment::STATUS_PENDING]);
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'order_id' => 'The order ID has already been used for this merchant.',
                ]);
            }

            throw $exception;
        }

        return response()->json(['data' => $payment], 201);
    }
}
