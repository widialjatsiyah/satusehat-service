<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClinicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Clinic::create([
            'code' => 'KLINIK-001',
            'name' => 'Klinik Contoh',
            'satusehat_client_id' => 'CLIENT_ID_EXAMPLE',
            'satusehat_client_secret' => 'CLIENT_SECRET_EXAMPLE', // encrypted by cast
            'api_shared_secret' => Str::random(32), // shared secret untuk HMAC
        ]);
    }
}
