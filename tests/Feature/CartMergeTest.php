<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Modules\Products\Models\Product;
use Modules\Products\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_merges_with_user_cart_directly()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a category and product
        $category = Category::create(['name' => 'Test Category']);
        $product = Product::create([
            'title' => 'Test Product',
            'description' => 'Test Product Description',
            'price' => 100.00,
            'stock' => 10,
            'category_id' => $category->id,
            'provider_id' => $user->id,
            'is_approved' => true,
        ]);

        // Simulate guest adding items to cart (session)
        $guestCart = [
            $product->id => [
                'product_id' => $product->id,
                'name' => $product->title,
                'price' => $product->price,
                'quantity' => 2,
            ]
        ];

        // Set session data
        session(['cart' => $guestCart]);
        
        // Simulate the cart merge by calling the mergeGuestCart method directly
        $controller = new \App\Http\Controllers\Auth\AuthenticatedSessionController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('mergeGuestCart');
        $method->setAccessible(true);
        
        // Call the merge method
        $method->invoke($controller, $user);

        // Verify cart was merged
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
        ]);

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);

        $cartItem = $cart->items()->where('product_id', $product->id)->first();
        $this->assertNotNull($cartItem);
        $this->assertEquals(2, $cartItem->quantity);
        $this->assertEquals($product->price, $cartItem->unit_price);

        // Verify guest cart was cleared
        $this->assertNull(session('cart'));
    }

    public function test_cart_merge_handles_duplicate_items()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a category and product
        $category = Category::create(['name' => 'Test Category']);
        $product = Product::create([
            'title' => 'Test Product',
            'description' => 'Test Product Description',
            'price' => 100.00,
            'stock' => 10,
            'category_id' => $category->id,
            'provider_id' => $user->id,
            'is_approved' => true,
        ]);

        // Create existing cart item for user
        $userCart = Cart::create(['user_id' => $user->id]);
        $userCart->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => $product->price,
        ]);

        // Simulate guest adding same product to cart
        $guestCart = [
            $product->id => [
                'product_id' => $product->id,
                'name' => $product->title,
                'price' => $product->price,
                'quantity' => 2,
            ]
        ];

        // Set session data
        session(['cart' => $guestCart]);
        
        // Simulate the cart merge
        $controller = new \App\Http\Controllers\Auth\AuthenticatedSessionController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('mergeGuestCart');
        $method->setAccessible(true);
        $method->invoke($controller, $user);

        // Verify quantities were added (3 + 2 = 5)
        $cartItem = $userCart->fresh()->items()->where('product_id', $product->id)->first();
        $this->assertEquals(5, $cartItem->quantity);

        // Verify guest cart was cleared
        $this->assertNull(session('cart'));
    }
}
