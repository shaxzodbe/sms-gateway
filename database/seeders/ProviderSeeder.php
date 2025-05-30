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
                'login' => 'it@texnomart.uz',
                'password' => 'sVPZVbuGKAD2FE8uFMyRG6GQVJoAVzaWr4NjR1f1',
                'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3NDk4ODM5ODAsImlhdCI6MTc0NzI5MTk4MCwicm9sZSI6InVzZXIiLCJzaWduIjoiOTY2OTgyMDZjOTJlNDJkNGI3MzI5MjRlN2MwYzkwY2Y4ODQ4MGUwYzM3MmY3NjQzMzU0ZjRmMWQ4MWM4Y2Q3NSIsInN1YiI6IjM2OTgifQ.gOa8ddmXjQL0v9GbcHopSVrYD5QoErHC_gmmG-k-x5Q',
                'endpoint' => 'https://notify.eskiz.uz/api/message/sms',
                'batch_size' => 200,
                'rps_limit' => 5,
            ],
            [
                'name' => 'Getsms',
                'priority' => 2,
                'is_active' => true,
                'login' => 'TRENDY',
                'password' => 'QtVgBgs2oIJ6qzWq1W7o',
                'endpoint' => 'http://185.8.212.184/smsgateway/',
                'token' => 'c5j9_eucmEkWacThExtgCVbT',
                'nickname' => 'texnomart',
                'batch_size' => 200,
                'rps_limit' => 5,
            ],
            [
                'name' => 'Playmobile',
                'priority' => 3,
                'is_active' => true,
                'login' => 'texnomart',
                'password' => 'TK]-kVk1aBi@',
                'endpoint' => 'https://send.smsxabar.uz',
                'batch_size' => 200,
                'rps_limit' => 5,
            ],
            [
                'name' => 'Notify',
                'priority' => 1,
                'is_active' => true,
                'login' => 'TEXNO',
                'password' => 'adminner1337',
                'token' => 'c5j9_eucmEkWacThExtgCVbT',
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
