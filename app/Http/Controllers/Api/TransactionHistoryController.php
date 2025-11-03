<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;

class TransactionHistoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'amount' => 'required|numeric',
            'admin_fee' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'status' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'amount' => $request->amount,
            'admin_fee' => $request->admin_fee ?? 0,
            'total_amount' => $request->total_amount ?? ($request->amount + ($request->admin_fee ?? 0)),
            'status' => $request->status ?? 'pending',
            'phone_number' => $request->phone_number,
            'notes' => $request->notes,
            'metadata' => $request->metadata,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil disimpan.',
            'data' => $transaction,
        ]);
    }

    public function index(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id())->recent();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }
}