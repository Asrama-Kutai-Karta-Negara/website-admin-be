<?php

namespace Database\Seeders;

use App\Models\CategoryGallery;
use App\Models\OriginCampus;
use App\Models\OriginCity;
use App\Models\Resident;
use App\Models\RoomNumber;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        CategoryGallery::insert([
            [
                'id' => (string) Str::uuid(),
                'name' => 'Fasilitas',
                'description' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Kegiatan & Aktifitas',
                'description' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Hiburan',
                'description' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);

        OriginCampus::factory()->count(16)->create();

        RoomNumber::factory()->count(48)->create();

        OriginCity::factory()->count(38)->create();

        Resident::factory()->count(100)->create();
    }
}
