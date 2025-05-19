<?php

namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Eskiz',
                'priority' => 4,
                'is_active' => true,
                'login' => '#########',
                'password' => '#########################',
                'token' => '################################',
                'endpoint' => 'https://notify.eskiz.uz/api/message/sms',
                'batch_size' => 200,
                'rps_limit' => 5,
            ],
            [
                'name' => 'Getsms',
                'priority' => 2,
                'is_active' => true,
                'login' => 'TRENDY',
                'password' => '##################',
                'endpoint' => '#############################',
                'token' => '###################################',
                'nickname' => '####################',
                'batch_size' => 200,
                'rps_limit' => 5,
            ],
            [
                'name' => 'Playmobile',
                'priority' => 3,
                'is_active' => true,
                'login' => '####################',
                'password' => '#################',
                'endpoint' => 'https://send.smsxabar.uz',
                'batch_size' => 200,
                'rps_limit' => 5,
            ],
            [
                'name' => 'Notify',
                'priority' => 1,
                'is_active' => true,
                'login' => 'TEXNO',
                'password' => '###################',
                'token' => '#######################',
                'endpoint' => 'https://notify.gov.uz/api/web/rest/send-bulk-sms',
                'batch_size' => 200,
                'rps_limit' => 5,
            ],
        ];

        foreach ($providers as $provider) {
            Provider::create($provider);
        }
    }
}
