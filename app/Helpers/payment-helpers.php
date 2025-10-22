<?php

use App\Models\Transaction;
function approvePayment($transaction_ref)
{
    $transaction = Transaction::where('reference', $transaction_ref)->first();
    if (!$transaction) return response()->json(['message' => 'Transaction not found'], 404);
    if ($transaction->amount) {
        $transaction->user->increment('wallet', $transaction->amount);
    }
    $transaction->status = 'APPROVED';
    $transaction->save();
    return response()->json(['message' => 'Payment approved']);
}
