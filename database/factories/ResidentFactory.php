<?php

namespace Database\Factories;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResidentModel>
 */
class ResidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Resident::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'age' => $this->faker->numberBetween(18, 30),
            'birth_date' => $this->faker->date('Y-m-d', '-18 years'),
            'address' => $this->faker->address(),
            'origin_city' => $this->faker->city(),
            'origin_campus' => $this->faker->company(), // Nama kampus sebagai nama perusahaan
            'phone_number' => $this->faker->optional()->phoneNumber(),
            'room_number' => $this->faker->randomDigitNotNull(),
            'status' => 'active',
        ];
    }
}
