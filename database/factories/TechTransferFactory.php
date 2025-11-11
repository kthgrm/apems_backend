<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TechTransfer>
 */
class TechTransferFactory extends Factory
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
            'name' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->word(),
            'purpose' => fake()->paragraph(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'tags' => fake()->words(3, true),
            'leader' => fake()->name(),
            'members' => fake()->name(),
            'deliverables' => fake()->sentence(),
            'agency_partner' => fake()->company(),
            'contact_person' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'copyright' => fake()->randomElement(['yes', 'no', 'pending']),
            'ip_details' => fake()->optional()->paragraph(),
            'attachment_paths' => null,
            'attachment_link' => fake()->optional()->url(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'remarks' => fake()->optional()->sentence(),
            'sdg_goals' => fake()->randomElements([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], fake()->numberBetween(1, 3)),
            'is_archived' => false,
        ];
    }
}
