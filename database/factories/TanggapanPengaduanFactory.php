<?php

namespace Database\Factories;

use App\Models\Pengaduan;
use App\Models\TanggapanPengaduan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TanggapanPengaduan>
 */
class TanggapanPengaduanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pengaduan_id' => Pengaduan::factory(),
            'admin_id' => User::factory(),
            'isi_tanggapan' => fake()->paragraph(),
        ];
    }
}
