<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\TechTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Modality>
 */
class ModalityFactory extends Factory
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
            'tech_transfer_id' => TechTransfer::factory(),
            'modality' => fake()->randomElement(['TV', 'Radio', 'Online', 'Print']),
            'tv_channel' => fake()->optional()->company(),
            'radio' => fake()->optional()->company(),
            'online_link' => fake()->optional()->url(),
            'time_air' => fake()->optional()->time(),
            'period' => fake()->optional()->word(),
            'partner_agency' => fake()->optional()->company(),
            'hosted_by' => fake()->optional()->name(),
            'is_archived' => false,
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
