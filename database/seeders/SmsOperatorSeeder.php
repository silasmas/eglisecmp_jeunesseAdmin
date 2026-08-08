<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Opérateur SMS Keccel actif (dump production).
 */
class SmsOperatorSeeder extends Seeder
{
    public function run(): void
    {
        $token = (string) env('KECCEL_TOKEN', env('SEED_KECCEL_TOKEN', 'KR9DP24WQK5BF4A'));
        $sender = (string) env('KECCEL_SENDER', env('SEED_KECCEL_SENDER', 'DGRAD'));

        DB::table('sms_operators')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Keccel',
                'provider' => 'keccel',
                'send_url' => 'https://api.keccel.com/sms/v2/message.asp',
                'balance_url' => 'https://api.keccel.com/sms/v2/balance.asp',
                'delivery_url' => 'https://api.keccel.com/sms/v2/delivery.asp',
                'token' => $token,
                'sender' => $sender,
                'send_method' => 'POST',
                'is_active' => 1,
                'remaining_sms' => 50,
                'last_balance_checked_at' => now(),
                'last_balance_response' => "{\r\n  \"balance\": \"50\",\r\n  \"expiration\": \"\",\r\n  \"status\": \"active\"\r\n}\r\n",
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command?->info('Opérateur SMS Keccel (id=1) enregistré.');
    }
}
