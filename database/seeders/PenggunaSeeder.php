<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PenggunaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'nama' => 'Ibu Sri Rahayu, S.T.',
                'email' => 'admin@bpbd.go.id',
                'nomor_telepon' => '08112345678',
                'password' => Hash::make('password'),
                'peran' => 'admin',
                'google_id' => null,
                'telegram_chat_id' => null,
                'kode_tautan_telegram' => null,
                'skor_kepercayaan' => 100,
            ],
            [
                'nama' => 'Agus Subekti',
                'email' => 'agus@tnbts.go.id',
                'nomor_telepon' => '08123456789',
                'password' => Hash::make('password'),
                'peran' => 'petugas',
                'google_id' => null,
                'telegram_chat_id' => '5149370987',
                'kode_tautan_telegram' => 'TNBTS-AGUS-01',
                'skor_kepercayaan' => 95,
            ],
            [
                'nama' => 'Rina Wulandari',
                'email' => 'rina@wisatawan.id',
                'nomor_telepon' => '08567891234',
                'password' => Hash::make('password'),
                'peran' => 'publik',
                'google_id' => null,
                'telegram_chat_id' => null,
                'kode_tautan_telegram' => 'WST-RINA-99',
                'skor_kepercayaan' => 50,
            ],
        ];

        foreach ($users as $user) {
            Pengguna::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
