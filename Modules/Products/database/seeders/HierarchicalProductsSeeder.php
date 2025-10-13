<?php

namespace Modules\Products\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;
use App\Models\User;

class HierarchicalProductsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Collect provider users
        $providerIds = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'provider'))
            ->pluck('id')
            ->toArray();

        // Fallback provider if none exist
        if (empty($providerIds)) {
            $fallbackProvider = User::firstOrCreate(
                ['email' => 'provider1@example.com'],
                ['name' => 'Default Provider', 'password' => bcrypt('Provider@123')]
            );
            $providerIds = [$fallbackProvider->id];
        }

        // Use deterministic names/slugs and updateOrCreate to avoid duplicates on re-run
        $numParents = 10;
        $numSubsPerParent = 5;
        $numProductsPerSub = 10;

        foreach (range(1, $numParents) as $i) {
            $parentCategoryName = 'Demo Collection ' . $i;
            $parentCategory = Category::updateOrCreate(
                [
                    'name' => $parentCategoryName,
                    'parent_id' => null,
                ],
                [
                    'description' => $faker->sentence(12),
                    'image' => $faker->imageUrl(640, 480, 'category', true, "Parent Category $i"),
                ]
            );

            foreach (range(1, $numSubsPerParent) as $j) {
                $subCategoryName = 'Demo Series ' . $i . '-' . $j;
                $subCategory = Category::updateOrCreate(
                    [
                        'name' => $subCategoryName,
                        'parent_id' => $parentCategory->id,
                    ],
                    [
                        'description' => $faker->sentence(15),
                        'image' => $faker->imageUrl(640, 480, 'subcategory', true, "Subcategory $i.$j"),
                    ]
                );

                foreach (range(1, $numProductsPerSub) as $k) {
                    $productTitle = 'Demo Product ' . $i . '-' . $j . '-' . $k;
                    $slug = Str::slug('demo-product-' . $i . '-' . $j . '-' . $k);
                    // Distribute providers deterministically
                    $providerId = $providerIds[($i + $j + $k) % count($providerIds)];

                    Product::updateOrCreate(
                        [
                            'slug' => $slug,
                        ],
                        [
                            'title' => $productTitle,
                            'description' => $faker->paragraph(3),
                            'price' => $faker->randomFloat(2, 10, 999),
                            'stock' => $faker->numberBetween(5, 100),
                            'image' => $faker->imageUrl(600, 600, 'product', true, $productTitle),
                            'category_id' => $subCategory->id,
                            'provider_id' => $providerId,
                            'is_approved' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Realistic hierarchical categories and products seeded successfully (idempotent).');
    }
}
