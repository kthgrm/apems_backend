<?php

namespace Database\Factories;

use App\Models\Resolution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resolution>
 */
class ResolutionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Resolution::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'attachment_paths' => [
                'resolution-attachments/' . fake()->uuid() . '.pdf',
            ],
            'user_id' => User::factory(),
            'is_archived' => false,
        ];
    }

    /**
     * Indicate that the resolution is archived.
     */
    public function archived(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_archived' => true,
        ]);
    }
}
