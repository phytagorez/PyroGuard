<?php

namespace Database\Factories;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Pengguna>
 */
class PenggunaFactory extends Factory
{
    /**
     * Nama model yang sesuai dengan factory.
     *
     * @var string
     */
    protected $model = Pengguna::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'nomor_telepon' => fake()->unique()->numerify('081#########'),
            'password' => Hash::make('password'),
            'peran' => 'publik',
            'skor_kepercayaan' => 50,
        ];
    }

    /**
     * State untuk akun petugas lapangan TNBTS.
     */
    public function petugas(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => 'petugas',
            'skor_kepercayaan' => 100,
        ]);
    }

    /**
     * State untuk akun admin BPBD.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => 'admin',
            'skor_kepercayaan' => 100,
        ]);
    }
}
