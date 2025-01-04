<?php

namespace Database\Factories;

use App\Models\RoomNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomNumber>
 */
class RoomNumberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = RoomNumber::class;
    public function definition(): array
    {
        $university = [];

        foreach (range(1, 12) as $number) {
            foreach (['A', 'B', 'C', 'D'] as $letter) {
                $university[] = "{$number}{$letter}";
            }
        }

        return [
            'name' => $this->faker->unique()->randomElement($university),
            'description' => $this->faker->sentence(),
        ];
    }
}
