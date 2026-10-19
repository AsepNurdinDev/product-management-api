<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Product::class);

        $validated = $request->validate([
            'q' => ['sometimes', 'string', 'max:150'],
            'category_id' => ['sometimes', 'integer', 'min:1'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0', 'gte:min_price'],
            'sort_by' => ['sometimes', 'in:name,price,stock,created_at'],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->user()->products()->with('category');

        if (! empty($validated['q'])) {
            $term = $validated['q'];
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }
        if (isset($validated['min_price'])) {
            $query->where('price', '>=', $validated['min_price']);
        }
        if (isset($validated['max_price'])) {
            $query->where('price', '<=', $validated['max_price']);
        }

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';

        return ProductResource::collection(
            $query->orderBy($sortBy, $sortDir)->paginate($validated['per_page'] ?? 15)->withQueryString()
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        Gate::authorize('create', Product::class);
        $validated = $request->validated();
        unset($validated['image']);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        try {
            $product = $request->user()->products()->create([
                ...$validated,
                'image_path' => $imagePath,
            ]);
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $exception;
        }

        return (new ProductResource($product->load('category')))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource|JsonResponse
    {
        Gate::authorize('update', $product);
        $validated = $request->validated();
        $newImagePath = null;
        $oldImagePath = $product->image_path;

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('products', 'public');
            $validated['image_path'] = $newImagePath;
        } elseif (($validated['remove_image'] ?? false) === true) {
            $validated['image_path'] = null;
        }

        unset($validated['image'], $validated['remove_image']);

        try {
            $product->update($validated);
        } catch (\Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        if ($oldImagePath && ($newImagePath || (($request->validated('remove_image') ?? false) === true))) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return new ProductResource($product->load('category'));
    }

    public function destroy(Product $product): Response
    {
        Gate::authorize('delete', $product);
        $imagePath = $product->image_path;
        $product->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return response()->noContent();
    }
}
