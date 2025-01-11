<?php

namespace Database\Factories;

use App\Models\OriginCampus;
use App\Models\OriginCity;
use App\Models\Resident;
use App\Models\RoomNumber;
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

        $age = $this->faker->numberBetween(18, 30);
        $birthDate = now()->subYears($age)->subMonths(rand(1, 12));

        $address = $this->faker->address();

        $roomNumberId = RoomNumber::all()->random()->id;

        $query = Resident::query();
        $query->where('room_number_id', $roomNumberId);
        $query->where('status', 'active');
        $existingActiveResident = $query->exists();

        $status = $existingActiveResident ? 'inactive' : 'active';

        return [
            'name' => $this->faker->name(),
            'age' => $age,
            'birth_date' => $birthDate->format('Y-m-d'),
            'address' => $address,
            'origin_city_id' => OriginCity::all()->random()->id,
            'origin_campus_id' => OriginCampus::all()->random()->id,
            'phone_number' => '6282' . $this->faker->numberBetween(100000000, 999999999),
            'room_number_id' => $roomNumberId,
            'status' => $status,
        ];
    }
}
