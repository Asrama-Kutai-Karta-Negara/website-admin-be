<?php

namespace Database\Factories;

use App\Models\OriginCampus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OriginCampus>
 */
class OriginCampusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = OriginCampus::class;

    public function definition(): array
    {
        $university = [
            'Universitas Gadjah Mada',
            'Universitas Negeri Yogyakarta',
            'Universitas Islam Indonesia',
            'Universitas Muhammadiyah Yogyakarta',
            'Institut Seni Indonesia Yogyakarta',
            'Universitas Sanata Dharma',
            'Universitas Kristen Duta Wacana',
            'Universitas Atma Jaya Yogyakarta',
            'Sekolah Tinggi Ilmu Ekonomi YKPN',
            'Universitas Ahmad Dahlan',
            'Universitas Pembangunan Nasional "Veteran" Yogyakarta',
            'Institut Teknologi Yogyakarta',
            'Universitas Mercu Buana Yogyakarta',
            'Universitas Tugu Yogyakarta',
            'Politeknik Negeri Yogyakarta',
            'Politeknik Amikom Yogyakarta',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($university),
            'description' => $this->faker->sentence(),
        ];
    }
}
