<?php

namespace Modules\Products\Database\Seeders;

use Illuminate\Database\Seeder;
 use Modules\Products\Models\Category;
 use Modules\Products\Models\Product;
 use App\Models\User;
 use Illuminate\Support\Facades\Hash;
 use Spatie\Permission\Models\Role;

class SeedProviderOneCatalog extends Seeder
{
    public function run(): void
    {
        $provider = User::query()
            ->where('name', 'Provider One')
            ->orWhereIn('email', ['provider1@example.com', 'rudrabaruwala.aids23@scet.ac.in'])
            ->first();

        if (! $provider) {
            // Create Provider One if missing
            $provider = User::firstOrCreate(
                ['email' => 'provider1@example.com'],
                [
                    'name' => 'Provider One',
                    'password' => Hash::make('Provider@123')
                ]
            );
        }

        // Ensure provider role is assigned
        if (! $provider->hasRole('provider')) {
            Role::firstOrCreate(['name' => 'provider']);
            $provider->assignRole('provider');
        }

        $providerId = $provider->id;

        Category::factory()->count(5)->create()->each(function (Category $category) use ($providerId) {
            Product::factory()->count(10)->create([
                'category_id' => $category->id,
                'provider_id' => $providerId,
            ]);
        });
    }
}


