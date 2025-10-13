<?php

namespace Modules\Products\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Products\Models\Product;
use Modules\Products\Models\Category;
use App\Models\User;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $providerId = User::query()->whereHas('roles', function ($q) { $q->where('name', 'provider'); })->inRandomOrder()->value('id')
            ?? User::query()->inRandomOrder()->value('id')
            ?? 1;

        return [
            'title' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'stock' => $this->faker->numberBetween(0, 100),
            'category_id' => Category::query()->inRandomOrder()->value('id') ?? Category::factory(),
            'provider_id' => $providerId,
            'image' => null,
            'is_approved' => true,
        ];
    }
}
