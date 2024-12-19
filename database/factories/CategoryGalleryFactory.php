<?php

namespace Database\Factories;

use App\Models\CategoryGallery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gallery>
 */
class CategoryGalleryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = CategoryGallery::class;
    public function definition(): array
    {
        return [
            'name' => 'Default Name',
            'description' => null,
        ];
    }
}
