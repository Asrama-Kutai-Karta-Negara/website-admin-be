<?php

namespace Database\Seeders;

use App\Models\CategoryGallery;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryGallerySeeder extends Seeder
{
    /**
     * Run the database seeds.
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
    }
}
