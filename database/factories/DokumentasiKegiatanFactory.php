<?php

namespace Database\Factories;

use App\Models\DokumentasiKegiatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DokumentasiKegiatan>
 */
class DokumentasiKegiatanFactory extends Factory
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
            'deskripsi' => fake()->paragraph(),
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'foto' => null,
            'fotos' => [],
            'status' => fake()->randomElement(DokumentasiKegiatan::STATUSES),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DokumentasiKegiatan::STATUS_PUBLISHED,
        ]);
    }
}
