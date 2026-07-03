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
        return [
            'user_id' => User::factory(),
            'judul' => fake()->sentence(4),
            'deskripsi' => fake()->paragraph(),
            'tanggal' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'gambar' => null,
            'status' => fake()->randomElement(ProgramBanjar::STATUSES),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramBanjar::STATUS_PUBLISHED,
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramBanjar::STATUS_SELESAI,
        ]);
    }
}
