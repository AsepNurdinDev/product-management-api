<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_search_filter_update_and_delete_products(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Elektronik']);
        $this->actingAs($user, 'sanctum');

        $created = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Keyboard Mekanik',
            'sku' => 'KEY-001',
            'description' => 'Keyboard untuk komputer',
            'price' => '450000.00',
            'stock' => 10,
        ])->assertCreated()->assertJsonPath('data.sku', 'KEY-001');

        $id = $created->json('data.id');
        $this->getJson('/api/products?q=Keyboard&min_price=400000&max_price=500000')
            ->assertOk()->assertJsonPath('data.0.id', $id);
        $this->getJson('/api/products?category_id='.$category->id)->assertOk();
        $this->patchJson("/api/products/{$id}", ['stock' => 5])
            ->assertOk()->assertJsonPath('data.stock', 5);
        $this->deleteJson("/api/products/{$id}")->assertNoContent();
    }

    public function test_user_cannot_access_or_mutate_another_users_product(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::create(['user_id' => $owner->id, 'name' => 'Milik owner']);
        $product = Product::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Produk owner',
            'sku' => 'OWNER-1',
            'price' => 100,
            'stock' => 1,
        ]);

        $this->actingAs($other, 'sanctum');
        $this->getJson("/api/products/{$product->id}")->assertForbidden();
        $this->patchJson("/api/products/{$product->id}", ['name' => 'Diambil alih'])->assertForbidden();
        $this->deleteJson("/api/products/{$product->id}")->assertForbidden();
        $this->getJson('/api/products')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_product_validation_rejects_foreign_category_and_invalid_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $foreignUser = User::factory()->create();
        $foreignCategory = Category::create(['user_id' => $foreignUser->id, 'name' => 'Kategori lain']);
        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/products', [
            'category_id' => $foreignCategory->id,
            'name' => 'Produk',
            'sku' => 'SKU-1',
            'price' => 100,
            'stock' => 1,
            'image' => UploadedFile::fake()->create('malware.php', 10, 'application/x-php'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['category_id', 'image']);
    }

    public function test_user_can_upload_product_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Foto']);
        $this->actingAs($user, 'sanctum');

        $response = $this->post('/api/products', [
            'category_id' => $category->id,
            'name' => 'Produk dengan gambar',
            'sku' => 'IMG-001',
            'price' => 25000,
            'stock' => 2,
            'image' => UploadedFile::fake()->image('produk.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $product = Product::where('sku', 'IMG-001')->firstOrFail();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }
}
