<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Engagement>
 */
class EngagementFactory extends Factory
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
            'college_id' => College::factory(),
            'agency_partner' => fake()->company(),
            'location' => fake()->city(),
            'activity_conducted' => fake()->sentence(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'number_of_participants' => fake()->numberBetween(10, 500),
            'faculty_involved' => fake()->name() . ', ' . fake()->name(),
            'narrative' => fake()->paragraph(),
            'attachment_paths' => null,
            'attachment_link' => fake()->optional()->url(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'remarks' => fake()->optional()->sentence(),
            'is_archived' => false,
        ];
    }
}
