<?php

namespace Database\Factories;

use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengaduan>
 */
class PengaduanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'judul' => fake()->sentence(4),
            'isi_pengaduan' => fake()->paragraph(),
            'foto' => null,
            'status' => fake()->randomElement(Pengaduan::STATUSES),
            'visibilitas' => fake()->randomElement(Pengaduan::VISIBILITAS),
            'reminded_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Pengaduan::STATUS_PENDING,
        ]);
    }
}
