<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCompleteWithdraw implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;

    public function __construct($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    public function handle()
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction || $transaction->status !== 'pending') {
            Log::info('⏩ AutoCompleteWithdraw: Transaction sudah tidak pending, lewati.', [
                'id' => $this->transactionId,
                'status' => optional($transaction)->status,
            ]);
            return;
        }

        DB::transaction(function () use ($transaction) {
            $user = $transaction->user;

            // Cek apakah user masih punya saldo cukup
            if ($user->balance >= $transaction->amount) {
                $user->balance -= $transaction->amount;
                $user->save();

                $transaction->status = 'success';
                $transaction->notes = '✅ Auto-confirmed (timer 60s, dev mode)';
                $transaction->save();

                Log::info('✅ AutoCompleteWithdraw: saldo dikurangi otomatis.', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                    'new_balance' => $user->balance,
                ]);
            } else {
                // Kalau saldo sudah tidak cukup, anggap gagal
                $transaction->status = 'failed';
                $transaction->notes = '❌ Auto confirm gagal — saldo tidak mencukupi';
                $transaction->save();

                Log::warning('⚠️ AutoCompleteWithdraw: saldo tidak cukup untuk auto confirm', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                ]);
            }
        });
    }
}