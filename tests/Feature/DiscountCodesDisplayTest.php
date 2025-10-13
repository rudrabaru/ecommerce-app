<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\User;
use Modules\Products\Models\Product;
use Modules\Products\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountCodesDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_codes_are_displayed_on_product_details_page()
    {
        // Create a user (provider)
        $user = User::factory()->create();

        // Create a category
        $category = Category::create(['name' => 'Fashion']);

        // Create a product
        $product = Product::create([
            'title' => 'Test Product',
            'description' => 'Test Product Description',
            'price' => 100.00,
            'stock' => 10,
            'category_id' => $category->id,
            'provider_id' => $user->id,
            'is_approved' => true,
        ]);

        // Create an active discount code for this category
        $discountCode = DiscountCode::create([
            'code' => 'FASHION20',
            'discount_type' => 'percentage',
            'discount_value' => 20.00,
            'minimum_order_amount' => 50.00,
            'usage_limit' => 100,
            'usage_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
        ]);

        // Attach to category via pivot
        $discountCode->categories()->attach($category->id);

        // Visit the product details page
        $response = $this->get(route('shop.details', $product->id));

        // Assert the page loads successfully
        $response->assertStatus(200);

        // Assert the discount code is displayed
        $response->assertSee('FASHION20');
        $response->assertSee('20% OFF');
        $response->assertSee('Min. order: $50.00');
    }

    public function test_inactive_discount_codes_are_not_displayed()
    {
        // Create a user (provider)
        $user = User::factory()->create();

        // Create a category
        $category = Category::create(['name' => 'Fashion']);

        // Create a product
        $product = Product::create([
            'title' => 'Test Product',
            'description' => 'Test Product Description',
            'price' => 100.00,
            'stock' => 10,
            'category_id' => $category->id,
            'provider_id' => $user->id,
            'is_approved' => true,
        ]);

        // Create an inactive discount code
        $discountCode = DiscountCode::create([
            'code' => 'INACTIVE20',
            'discount_type' => 'percentage',
            'discount_value' => 20.00,
            'is_active' => false,
        ]);

        // Attach to category via pivot
        $discountCode->categories()->attach($category->id);

        // Visit the product details page
        $response = $this->get(route('shop.details', $product->id));

        // Assert the page loads successfully
        $response->assertStatus(200);

        // Assert the inactive discount code is not displayed
        $response->assertDontSee('INACTIVE20');
    }

    public function test_expired_discount_codes_are_not_displayed()
    {
        // Create a user (provider)
        $user = User::factory()->create();

        // Create a category
        $category = Category::create(['name' => 'Fashion']);

        // Create a product
        $product = Product::create([
            'title' => 'Test Product',
            'description' => 'Test Product Description',
            'price' => 100.00,
            'stock' => 10,
            'category_id' => $category->id,
            'provider_id' => $user->id,
            'is_approved' => true,
        ]);

        // Create an expired discount code
        $discountCode = DiscountCode::create([
            'code' => 'EXPIRED20',
            'discount_type' => 'percentage',
            'discount_value' => 20.00,
            'valid_from' => now()->subMonth(),
            'valid_until' => now()->subDay(),
            'is_active' => true,
        ]);

        // Attach to category via pivot
        $discountCode->categories()->attach($category->id);

        // Visit the product details page
        $response = $this->get(route('shop.details', $product->id));

        // Assert the page loads successfully
        $response->assertStatus(200);

        // Assert the expired discount code is not displayed
        $response->assertDontSee('EXPIRED20');
    }
}
