<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\TechTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImpactAssessment>
 */
class ImpactAssessmentFactory extends Factory
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
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'attachment_paths' => null,
            'is_archived' => false,
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
