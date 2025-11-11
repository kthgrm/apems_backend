<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Award>
 */
class AwardFactory extends Factory
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
            'award_name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'date_received' => fake()->date(),
            'event_details' => fake()->paragraph(),
            'location' => fake()->city(),
            'awarding_body' => fake()->company(),
            'people_involved' => fake()->name() . ', ' . fake()->name(),
            'attachment_paths' => null,
            'attachment_link' => fake()->optional()->url(),
            'is_archived' => false,
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
