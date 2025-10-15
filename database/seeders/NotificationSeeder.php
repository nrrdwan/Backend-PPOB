<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        Notification::create([
            'user_id' => 1,
            'title' => 'Deposit Berhasil',
            'message' => 'Top up sebesar Rp50.000 telah masuk ke saldo kamu.',
            'type' => 'deposit_success',
        ]);

        Notification::create([
            'user_id' => 1,
            'title' => 'Transaksi Gagal',
            'message' => 'Pembayaran pulsa gagal, saldo kamu telah dikembalikan.',
            'type' => 'deposit_failed',
        ]);
    }
}