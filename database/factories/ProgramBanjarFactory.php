<?php

namespace Database\Factories;

use App\Models\ProgramBanjar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramBanjar>
 */
class ProgramBanjarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggalMulai = fake()->dateTimeBetween('-1 month', '+2 months');
        $tanggalSelesai = (clone $tanggalMulai)->modify('+'.fake()->numberBetween(0, 7).' days');

        return [
            'user_id' => User::factory(),
            'judul' => fake()->sentence(4),
            'deskripsi' => fake()->paragraph(),
            'tanggal' => $tanggalMulai->format('Y-m-d'),
            'tanggal_mulai' => $tanggalMulai->format('Y-m-d'),
            'tanggal_selesai' => $tanggalSelesai->format('Y-m-d'),
            'gambar' => null,
            'status' => fake()->randomElement(ProgramBanjar::STATUSES),
        ];
    }

    public function berjalan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramBanjar::STATUS_BERJALAN,
        ]);
    }

    public function published(): static
    {
        return $this->berjalan();
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramBanjar::STATUS_SELESAI,
        ]);
    }
}
