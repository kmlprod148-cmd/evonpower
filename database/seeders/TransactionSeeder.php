<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        $transactions = [
            [
                'transaction_id' => 'TR-78945',
                'user_id' => 1,
                'charging_point_id' => 1,
                'start_time' => now()->subHours(3),
                'end_time' => now()->subHours(2),
                'energy_consumed' => 23.4,
                'duration' => 3600, // 1 hour in seconds
                'amount' => 15.60,
                'status' => 'completed',
            ],
            [
                'transaction_id' => 'TR-78944',
                'user_id' => 2,
                'charging_point_id' => 3,
                'start_time' => now()->subHours(5),
                'end_time' => now()->subHours(3.5),
                'energy_consumed' => 42.7,
                'duration' => 5400, // 1.5 hours in seconds
                'amount' => 28.40,
                'status' => 'completed',
            ],
            [
                'transaction_id' => 'TR-78943',
                'user_id' => 1,
                'charging_point_id' => 5,
                'start_time' => now()->subHours(1),
                'end_time' => null,
                'energy_consumed' => 18.2,
                'duration' => 3600,
                'amount' => 12.10,
                'status' => 'in_progress',
            ],
        ];
        
        foreach ($transactions as $transaction) {
            Transaction::create($transaction);
        }
    }
}