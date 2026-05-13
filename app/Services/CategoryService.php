<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Exceptions\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Database\Capsule\Manager as DB;

class CategoryService
{
    public function allCategoriesData(): array
    {
        return [
            'categories' => Category::with(['parent', 'children'])->withCount('products')
                ->where('is_active', true)->get(),
            'featured_products' => Product::featured()->get()
        ];
    }

    public function singleCategoryData(int $categoryId): array
    {
        if (!$category = Category::with(['parent', 'children', 'products'])->find($categoryId)) {
            throw new ValidationException('Failed to retrieve category', ['error' => 'Category not found. Kindly check that your request is picking up a correct category ID']);
        }

        return ['categories' => $category];
    }

    public function storeCategory(ServerRequestInterface $request): void
    {
        try {
            $data = $request->getParsedBody();

            // Validate required fields
            $required = ['name', 'slug'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new ValidationException("Incomplete form", ['error' => "Field {$field} is required"]);
                }
            }

            // Check if slug is unique
            $existingCategory = Category::where('slug', $data['slug'])->first();
            if ($existingCategory) {
                throw new ValidationException('Slug Exists', ['error' => 'Slug must be unique']);
            }

            // Validate parent_id if provided
            if (!empty($data['parent_id'])) {
                $parent = Category::find($data['parent_id']);
                if (!$parent) {
                    throw new ValidationException("Unknown Parent", ['error' => 'Parent category not found. Kindly check that your request is picking up a correct category ID']);
                }
            }

            Category::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null,
                'image_url' => $this->handleCategoryImageUpload($request, $data['slug']),
                'is_active' => $data['is_active'] === 'on' ? true : false
            ]);

            $_SESSION['success'] = 'Category created successfully';
        } catch (\Exception $e) {
            throw new ValidationException("Category not Saved", ['error' => 'Failed to create category: ' . $e->getMessage()]);
        }
    }

    public function updateCategory(ServerRequestInterface $request, int $categoryId): void
    {
        try {
            $category = Category::find($categoryId);

            if (!$category) {
                throw new ValidationException('Category not found',['error'=>'Kindly check that your request is picking up a correct category ID']);
            }

            $data = $request->getParsedBody();

            // Validate slug uniqueness if changing
            if (isset($data['slug']) && $data['slug'] !== $category->slug) {
                $existingCategory = Category::where('slug', $data['slug'])->first();
                if ($existingCategory) {
                    throw new ValidationException('Slug Exists', ['error' => 'Slug must be unique']);
                }
            }

            // Validate parent_id if provided
            if (isset($data['parent_id']) && $data['parent_id'] != null && !empty($data['parent_id'])) {
                if ($data['parent_id'] == $category->id) {
                    throw new ValidationException('No Self-parenting', ['error' => 'Category cannot be its own parent']);
                }

                if (!empty($data['parent_id'])) {
                    $parent = Category::find($data['parent_id']);
                    if (!$parent) {
                        throw new ValidationException("Unknown Parent", ['error' => 'Parent category not found']);
                    }
                }
            } else {
                $data['parent_id'] = null;
            }

            $updateData = [
                'name' => $data['name'] ?? $category->name,
                'slug' => $data['slug'] ?? $category->slug,
                'description' => $data['description'] ?? $category->description,
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
                'is_active' => $data['is_active'] == 'on' ? true : false ?? $category->is_active
            ];

            // Handle image upload if provided
            if (!empty($_FILES['image'])) {
                $newImage = $this->handleCategoryImageUpload($request, $data['slug']);
                if ($newImage) {
                    // Delete old image if exists
                    if ($category->image_url) {
                        $oldImagePath = $_SERVER['DOCUMENT_ROOT'] . $category->image_url;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $updateData['image_url'] = $newImage;
                }
            }

            $category->update($updateData);

            $_SESSION['success'] = 'Category updated successfully';
        } catch (\Exception $e) {
            throw new ValidationException("Category not Updated", ['error' => 'Failed to update category: ' . $e->getMessage()]);
        }
    }

    public function destroyCategory(int $categoryId)
    {

        DB::beginTransaction();

        try {
            $category = Category::find($categoryId);

            if ($category instanceof \Illuminate\Database\Eloquent\Collection) {
                $category = $category->first();
            }

            if (!$category) {
                throw new ValidationException('Category not found',['error'=>'Kindly check that your request is picking up a correct category ID']);
            }

            // Check if category has products
            $productCount = Product::where('category_id', $categoryId)->count();
            if ($productCount > 0) {
                throw new ValidationException('Category has Products',['error'=>'Cannot delete category that has products. Please reassign or delete the products first.']);
            }

            // Check if category has subcategories
            $subcategoryCount = Category::where('parent_id', $categoryId)->count();
            if ($subcategoryCount > 0) {
                throw new ValidationException("Category is a Parent Category",['error'=>'Cannot delete category that has subcategories. Please delete or reassign the subcategories first.']);
            }

            // Delete category image if exists
            if ($category->image_url) {
                $imagePath = $_SERVER['DOCUMENT_ROOT'] . $category->image_url;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $category->delete();

            DB::commit();

            $_SESSION['success'] = 'Category deleted successfully';
        } catch (\Exception $e) {
            DB::rollBack();
            $_SESSION;
            throw new ValidationException("Category not Deleted", ['error' => 'Failed to delete category: ' . $e->getMessage()]);
        }
    }

    public function categoryTree(): array
    {

        try {
            $categories = Category::with([
                'children' => function ($query) {
                    $query->where('is_active', true);
                }
            ])->whereNull('parent_id')->where('is_active', true)->get();

            $hierarchy = $this->buildCategoryTree($categories);

            return [
                'success' => true,
                'data' => $hierarchy,
                'message' => 'Category hierarchy retrieved successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve category hierarchy',
                'error' => $e->getMessage()
            ];
        }
    }

    // Helper method to build category tree
    private function buildCategoryTree($categories)
    {
        return $categories->map(function ($category) {
            $data = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'product_count' => $category->products_count ?? 0
            ];

            if ($category->children->isNotEmpty()) {
                $data['children'] = $this->buildCategoryTree($category->children);
            }

            return $data;
        });
    }

    // Handle category image upload
    private function handleCategoryImageUpload(ServerRequestInterface $request, $slug): ?string
    {
        if (empty($_FILES['image'])) {
            return null;
        }

        $image = $_FILES['image'];

        if ($image['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/images/categories/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $filename = $slug . 'corey.co.ke' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($image['tmp_name'], $filepath)) {
            return '/images/categories/' . $filename;
        }

        return null;
    }

}