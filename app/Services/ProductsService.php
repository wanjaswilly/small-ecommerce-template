<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTag;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Database\Capsule\Manager as DB;

class ProductsService
{
    public function allProductsData(ServerRequestInterface $request): array
    {
        # Get query parameters
        $queryParams = $request->getQueryParams();
        $searchQuery = $queryParams['search'] ?? null;
        $categorySlug = $queryParams['category'] ?? null;
        $priceRange = $queryParams['price'] ?? null;
        $sort = $queryParams['sort'] ?? 'newest';
        $page = $queryParams['page'] ?? 1;
        $perPage = 24;

        # Build query for all products
        $productsQuery = Product::with(['category', 'tags'])
            ->withAvg('approvedReviews', 'rating')
            ->withCount('approvedReviews as reviews_count')
            ->where('is_active', true);

        # Apply search filter
        if ($searchQuery) {
            $productsQuery->where(function ($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('description', 'like', '%' . $searchQuery . '%');
            });
        }

        # Apply category filter
        if ($categorySlug) {
            $productsQuery->whereHas('category', function ($query) use ($categorySlug) {
                $query->where('slug', $categorySlug);
            });
        }

        # Apply price range filter
        if ($priceRange) {
            if ($priceRange === '0-10000') {
                $productsQuery->where('price', '<=', 10000);
            } elseif ($priceRange === '10000-30000') {
                $productsQuery->whereBetween('price', [10000, 30000]);
            } elseif ($priceRange === '30000-60000') {
                $productsQuery->whereBetween('price', [30000, 60000]);
            } elseif ($priceRange === '60000-100000') {
                $productsQuery->whereBetween('price', [60000, 100000]);
            } elseif ($priceRange === '100000-') {
                $productsQuery->where('price', '>=', 100000);
            }
        }

        # Apply sorting
        switch ($sort) {
            case 'price_low':
                $productsQuery->orderBy('price', 'asc');
                break;
            case 'price_high':
                $productsQuery->orderBy('price', 'desc');
                break;
            case 'name':
                $productsQuery->orderBy('name', 'asc');
                break;
            default:
                $productsQuery->orderBy('created_at', 'desc');
                break;
        }

        # Get paginated products
        $products = $productsQuery->paginate($perPage, ['*'], 'page', $page);

        # Build query string for pagination links
        $queryString = '';
        if ($searchQuery)
            $queryString .= '&search=' . urlencode($searchQuery);
        if ($categorySlug)
            $queryString .= '&category=' . urlencode($categorySlug);
        if ($priceRange)
            $queryString .= '&price=' . urlencode($priceRange);
        if ($sort && $sort !== 'newest')
            $queryString .= '&sort=' . urlencode($sort);

        # Get categories for filter
        $categories = Category::where('is_active', true)->get();

        return [
            'products' => $products,
            'categories' => $categories,
            'search_query' => $searchQuery,
            'selected_category' => $categorySlug,
            'selected_price' => $priceRange,
            'selected_sort' => $sort,
            'query_string' => $queryString,
        ];
    }

    public function categoryData(ServerRequestInterface $request, string $categorySlug="" ): array
    {
        if($categorySlug !=""){

        $category = Category::where('slug', $categorySlug)->first();

        if (!$category) {
            throw new ValidationException('Category not Found', ['error' => 'Category not found']);
        }

        # Get query parameters for filtering
        $queryParams = $request->getQueryParams();
        $searchQuery = $queryParams['search'] ?? null;
        $brand = $queryParams['brand'] ?? null;
        $priceRange = $queryParams['price'] ?? null;
        $sort = $queryParams['sort'] ?? 'newest';
        $page = $queryParams['page'] ?? 1;
        $perPage = 24;

        # Build query for products in this category
        $productsQuery = Product::with(['category', 'tags'])
            ->where('category_id', $category->id)
            ->where('is_active', true);

        # Apply search filter
        if ($searchQuery) {
            $productsQuery->where(function ($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('description', 'like', '%' . $searchQuery . '%');
            });
        }

        # Apply price range filter
        if ($priceRange) {
            if ($priceRange === '0-10000') {
                $productsQuery->where('price', '<=', 10000);
            } elseif ($priceRange === '10000-30000') {
                $productsQuery->whereBetween('price', [10000, 30000]);
            } elseif ($priceRange === '30000-60000') {
                $productsQuery->whereBetween('price', [30000, 60000]);
            } elseif ($priceRange === '60000-100000') {
                $productsQuery->whereBetween('price', [60000, 100000]);
            } elseif ($priceRange === '100000-') {
                $productsQuery->where('price', '>=', 100000);
            }
        }

        # Apply sorting
        switch ($sort) {
            case 'price_low':
                $productsQuery->orderBy('price', 'asc');
                break;
            case 'price_high':
                $productsQuery->orderBy('price', 'desc');
                break;
            case 'name':
                $productsQuery->orderBy('name', 'asc');
                break;
            default:
                $productsQuery->orderBy('created_at', 'desc');
                break;
        }

        # Get paginated products
        $products = $productsQuery->paginate($perPage, ['*'], 'page', $page);

        # Build query string for pagination links
        $queryString = '';
        if ($searchQuery)
            $queryString .= '&search=' . urlencode($searchQuery);
        if ($brand)
            $queryString .= '&brand=' . urlencode($brand);
        if ($priceRange)
            $queryString .= '&price=' . urlencode($priceRange);
        if ($sort && $sort !== 'newest')
            $queryString .= '&sort=' . urlencode($sort);

        return [
            'category' => $category,
            'products' => $products,
            'search_query' => $searchQuery,
            'selected_brand' => $brand,
            'selected_price' => $priceRange,
            'selected_sort' => $sort,
            'query_string' => $queryString,
        ];
        }
        return ['categories'=>Category::with('products')->get()];
    }

    public function parentCategories()
    {
        return [
            'parent_categories' => Category::whereNull('parent_id')->orderBy('name')->get()
        ];
    }

    public function singleProductData(string $slug): array
    {
        $product = Product::with(['category', 'tags'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            throw new ValidationException("Product Not Found", ['error' => 'Product not found']);
        }

        $product->increment('product_views');

        # Get related products from same category
        $relatedProducts = Product::with(['category', 'tags'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(8)
            ->get();
        # Get approved reviews for this product
        $reviewService = new \App\Services\ReviewService();
        $reviews = $reviewService->getApprovedReviews($product->id);
        $ratingDistribution = $reviewService->getRatingDistribution($product->id);

        return [
            'product' => $product,
            'related_products' => $relatedProducts,
            'reviews' => $reviews,
            'rating_distribution' => $ratingDistribution,
        ];
    }

    public function singleProductById(int $productId): array
    {
        $product = Product::with(['category', 'tags'])->find($productId);

        if (!$product) {
            throw new ValidationException("Product Not Found", ['error' => 'Product not found']);
        }

        return [
            'product' => $product,
            'categories' => $this->activeCategories()
        ];
    }

    public function activeCategories(): array
    {
        return [
            'categories' => Category::where('is_active', true)->get()
        ];
    }

    public function saveProduct(ServerRequestInterface $request): array
    {

        DB::beginTransaction();

        try {
            $data = $request->getParsedBody();

            # Validate required fields
            $required = ['name', 'description', 'price', 'sku', 'category_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Field {$field} is required");
                }
            }

            # Check if category exists
            $category = Category::find($data['category_id']);
            if (!$category) {
                throw new \Exception('Invalid category');
            }

            $slug = $this->slugify($data['name']);

            # Prepare attributes
            $attributes = [];
            if (!empty($data['attributes'])) {
                $attributes = is_array($data['attributes']) ? $data['attributes'] : json_decode($data['attributes'], true);
            }

            $data['is_active'] == 'on' ? $isActive = true : $isActive = false;
            $data['featured'] == 'on' ? $isFeatured = true : $isFeatured = false;
            # Create product
            $product = Product::create([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'],
                'price' => $data['price'],
                'sku' => $data['sku'],
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'min_stock_level' => $data['min_stock_level'] ?? 5,
                'category_id' => $data['category_id'],
                'attributes' => $attributes,
                'images' => $this->handleImageUpload($request, $category->slug, $slug),
                'is_active' => $isActive,
                'featured' => $isFeatured
            ]);

            # Handle dynamic tags
            if (!empty($data['tags']) && is_array($data['tags'])) {
                foreach ($data['tags'] as $tag) {
                    if (!empty($tag['name']) && !empty($tag['value'])) {
                        ProductTag::create([
                            'product_id' => $product->id,
                            'tag_name' => $tag['name'],
                            'tag_value' => $tag['value'],
                        ]);
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'data' => $product,
                'message' => 'Product created successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ValidationException("Error While Saving Product", ['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    public function updateProduct(ServerRequestInterface $request, int $productId): array
    {

        DB::beginTransaction();

        try {
            $product = Product::find($productId);
            if ($product instanceof \Illuminate\Database\Eloquent\Collection) {
                $product = $product->first();
            }

            if (!$product) {
                throw new ValidationException("Product not found", ['error' => 'Product not found']);
            }

            $data = $request->getParsedBody();
            $slug = $this->slugify($data['name'] ?? $product->name);

            # === Update attributes ===
            # Prepare attributes
            $attributes = [];
            if (!empty($data['attributes'])) {
                $attributes = is_array($data['attributes']) ? $data['attributes'] : json_decode($data['attributes'], true);
            }


            $data['is_active'] == 'on' ? $isActive = true : $isActive = false;
            $data['featured'] == 'on' ? $isFeatured = true : $isFeatured = false;

            # === Update main fields ===
            $product->name = $data['name'] ?? $product->name;
            $product->slug = $slug;
            $product->description = $data['description'] ?? $product->description;
            $product->price = $data['price'] ?? $product->price;
            $product->stock_quantity = $data['stock_quantity'] ?? $product->stock_quantity;
            $product->min_stock_level = $data['min_stock_level'] ?? $product->min_stock_level;
            $product->attributes = $attributes;
            $product->is_active = $isActive;
            $product->featured = $isFeatured;
            $product->save();

            # === Handle image uploads ===
            $categorySlug = $product->category->slug ?? 'uncategorized';

            $product->images = $this->handleImageUpdate(
                $request,
                $product,
                $categorySlug,
                $slug
            );

            $product->save();

            # === Update tags ===
            if (!empty($data['tags']) && is_array($data['tags'])) {
                ProductTag::where('product_id', $product->id)->delete();

                foreach ($data['tags'] as $tag) {
                    if (!empty($tag['name']) && !empty($tag['value'])) {
                        ProductTag::create([
                            'product_id' => $product->id,
                            'tag_name' => $tag['name'],
                            'tag_value' => $tag['value'],
                        ]);
                    }
                }
            }

            DB::commit();

            return ['success' => 'Product updated successfully'];
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ValidationException("Error While Updating Product", ['error' => 'Failed to update the product: ' . $e->getMessage()]);
        }

    }

    public function destroyProduct(int $productId): array
    {
        DB::beginTransaction();

        try {
            $product = Product::find($productId);

            if (!$product) {
                throw new ValidationException("Product not found", ['error' => 'Product not found']);
            }

            # Delete associated images
            if ($product->images) {
                foreach ($product->images as $image) {
                    $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/public/' . $image;
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }

            $product->delete();
            ProductTag::where('product_id', $product->id)->delete();

            DB::commit();

            return ['success' => 'Product deleted successfully'];
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ValidationException("Error While Updating Product", ['error' => 'Failed to delete the product: ' . $e->getMessage()]);
        }
    }


    #todo : create method to get a product delivery prices from productDeliveryPrice model
    #todo: the method checks for the product with highest delivery price and if the rest of the 
    #todo: ... products are of low price and below 3, return the highest, if above 4 products return
    # ... highest+average of the rest, 
    # the products will have to have classes, where each class has max no of items to retain the same 
    # delivery price eg smallest = 20(earphones, usbs etc), small= 10(tshirts), medium=5, big=2 with a 
    # free delivery option if total value hit free delivery ceiling.
    # WOULD BE BETTER IF THIS IS MADE INTO A SERVICE.
    

    #todo: create productDeliveryPrice model where each product has its own delivery price and delivery class

    
    # Handle image upload
    private function handleImageUpload(ServerRequestInterface $request, string $categorySlug, string $slug): array
    {
        $uploadedImages = [];

        if (empty($_FILES['images'])) {
            return $uploadedImages;
        }

        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/images/products/' . $categorySlug . '/';

        # Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $images = $_FILES['images'];

        # Handle single file or multiple files
        if (is_array($images['name'])) {
            foreach ($images['name'] as $key => $name) {
                if ($images['error'][$key] === UPLOAD_ERR_OK) {
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $filename = $slug . '_' . uniqid() . '.' . $extension;
                    $filepath = $uploadDir . $filename;

                    if (move_uploaded_file($images['tmp_name'][$key], $filepath)) {
                        $uploadedImages[] = '/images/products/' . $categorySlug . '/' . $filename;
                    }
                }
            }
        } else {
            if ($images['error'] === UPLOAD_ERR_OK) {
                $extension = pathinfo($images['name'], PATHINFO_EXTENSION);
                $filename = $slug . '_' . uniqid() . '.' . $extension;
                $filepath = $uploadDir . $filename;

                if (move_uploaded_file($images['tmp_name'], $filepath)) {
                    $uploadedImages[] = '/images/products/' . $categorySlug . '/' . $filename;
                }
            }
        }

        return $uploadedImages;
    }
    private function handleImageUpdate(ServerRequestInterface $request, Product $product, string $categorySlug, string $slug): array
    {
        $finalImages = [];
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/images/products/' . $categorySlug . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        # 1. Keep existing images (already ordered from frontend)
        $existingImages = $request->getParsedBody()['existing_images'] ?? [];

        if (!is_array($existingImages)) {
            $existingImages = [];
        }

        $finalImages = $existingImages;

        # 2. Upload new ones
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $filename = $slug . '_' . uniqid() . '.' . $extension;
                $filepath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $filepath)) {
                    $finalImages[] = '/images/products/' . $categorySlug . '/' . $filename;
                }
            }
        }

        # 3. Delete removed images from disk
        $oldImages = $product->images ?? [];
        $deletedImages = array_diff($oldImages, $finalImages);

        foreach ($deletedImages as $img) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $img;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        return array_values($finalImages); # reset indexes
    }


    # Slugify helper
    public function slugify($text, string $divider = '-'): string
    {
        # replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        # transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, $divider);

        # remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public function productSearch(ServerRequestInterface $request): array
    {
        $searchQuery = trim($request->getQueryParams()['query'] ?? $request->getQueryParams()['q']);
        return [
            'products' => Product::where('is_active', true)
                ->where(function ($query) use ($searchQuery) {
                    $query->where('name', 'like', '%' . $searchQuery . '%')
                        ->orWhere('description', 'like', '%' . $searchQuery . '%');
                })->limit(12)->get(['id', 'name', 'price'])->toArray()
        ];
    }
}