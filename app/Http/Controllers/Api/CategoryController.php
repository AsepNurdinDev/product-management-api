<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Category::class);

        return CategoryResource::collection(
            request()->user()->categories()->withCount('products')->latest()->paginate(min(max((int) request('per_page', 15), 1), 100))
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        Gate::authorize('create', Category::class);
        $category = $request->user()->categories()->create($request->validated());

        return (new CategoryResource($category->loadCount('products')))
            ->response()->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        Gate::authorize('view', $category);

        return new CategoryResource($category->loadCount('products'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        Gate::authorize('update', $category);
        $category->update($request->validated());

        return new CategoryResource($category->loadCount('products'));
    }

    public function destroy(Category $category): Response|JsonResponse
    {
        Gate::authorize('delete', $category);

        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.',
            ], 409);
        }

        $category->delete();

        return response()->noContent();
    }
}
