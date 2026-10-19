<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_manage_own_categories(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $created = $this->postJson('/api/categories', [
            'name' => 'Elektronik',
            'description' => 'Perangkat elektronik',
        ])->assertCreated()->assertJsonPath('data.name', 'Elektronik');

        $id = $created->json('data.id');
        $this->getJson('/api/categories')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->patchJson("/api/categories/{$id}", ['description' => 'Deskripsi baru'])
            ->assertOk()->assertJsonPath('data.description', 'Deskripsi baru');
        $this->deleteJson("/api/categories/{$id}")->assertNoContent();
    }

    public function test_user_cannot_view_or_mutate_another_users_category(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::create(['user_id' => $owner->id, 'name' => 'Rahasia']);

        $this->actingAs($otherUser, 'sanctum');
        $this->getJson("/api/categories/{$category->id}")->assertForbidden();
        $this->patchJson("/api/categories/{$category->id}", ['name' => 'Diubah'])->assertForbidden();
        $this->deleteJson("/api/categories/{$category->id}")->assertForbidden();
    }

    public function test_category_name_must_be_unique_per_user(): void
    {
        $user = User::factory()->create();
        Category::create(['user_id' => $user->id, 'name' => 'Elektronik']);
        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/categories', ['name' => 'Elektronik'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }
}
