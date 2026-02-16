<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Exception;
use Psr\Http\Message\ServerRequestInterface;

class AdminService
{
    private StatsService $statsService;
    private ProductsService $productsService;

    public function dashboardData(): array
    {
        return array_merge([
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'recent_products' => Product::latest()->limit(5)->get(),
            'low_stock_products' => Product::lowStock()->limit(5)->get(),
            'low_stock_count' => Product::lowStock()->count(),
            'total_users' => User::count(),
            'total_orders' => Order::count(),
            'recent_orders' => Order::with('user')->latest()->limit(5)->get(),
        ], $this->statsService->aggregatedStats());
    }

    public function productsList(): array
    {
        return [
            'products' => Product::with(['category'])->latest()->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get()
        ];
    }

    public function allOrders(): array
    {
        return [
            'orders' => Order::with(['user', 'items.product'])->latest()->get(),
        ];
    }

    public function allCustomers(): array
    {
        return [
            'customers' => User::where('role', 'customer')->latest()->get()
        ];
    }

    public function inventoryData(ServerRequestInterface $request): array
    {

        $queryParams = $request->getQueryParams();
        $page = $queryParams['page'] ?? 1;
        $perPage = 24;

        $inventory = Product::with(['category'])
            ->where('is_active', true)
            ->orderBy('stock_quantity', 'asc')
            ->paginate($perPage, ['*'], $page);

        $totalProducts = Product::count();
        $lowStockCount = Product::lowStock()->count();
        $outOfStockCount = Product::outOfStock()->count();

        return [
            'inventory' => $inventory,
            'total_products' => $totalProducts,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount
        ];
    }

    public function analyticsData(): array
    {
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total_amount');
        $totalOrders = Order::count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $newCustomers = User::newCustomers()->count();
        $totalCustomers = User::customer()->count();

        # Calculate conversion rate: customers with orders vs total customers
        $customersWithOrders = User::customer()->whereHas('orders')->count();
        $conversionRate = $totalCustomers > 0 ? ($customersWithOrders / $totalCustomers) * 100 : 0;

        # Calculate total revenue for percentage calculations
        $totalRevenueForPercentage = $totalRevenue > 0 ? $totalRevenue : 1;

        $salesByCategory = Category::withCount(['products'])
            ->with([
                'products' => function ($query) {
                    $query->withSum('orderItems', 'total_price');
                }
            ])
            ->get()
            ->map(function ($category) use ($totalRevenueForPercentage) {
                $revenue = $category->products->sum('order_items_sum_total_price') ?? 0;
                return [
                    'name' => $category->name,
                    'revenue' => $revenue,
                    'percentage' => $totalRevenueForPercentage > 0 ? ($revenue / $totalRevenueForPercentage) * 100 : 0
                ];
            })
            ->filter(function ($category) {
                return $category['revenue'] > 0; # Only show categories with sales
            })
            ->sortByDesc('revenue')
            ->values();

        $topProducts = Product::with(['category'])
            ->withSum('orderItems', 'quantity')
            ->withSum('orderItems', 'total_price')
            ->whereHas('orderItems') # Only products that have been sold
            ->orderBy('order_items_sum_quantity', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                $attributes = $product->attributes ?? [];
                return [
                    'name' => $product->name,
                    'category' => $product->category->name,
                    'type' => $attributes['product_type'] ?? 'general',
                    'sales' => $product->order_items_sum_quantity ?? 0,
                    'revenue' => $product->order_items_sum_total_price ?? 0
                ];
            });
        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'avg_order_value' => round($avgOrderValue, 2),
            'new_customers' => $newCustomers,
            'total_customers' => $totalCustomers,
            'conversion_rate' => round($conversionRate, 1),
            'sales_by_category' => $salesByCategory,
            'top_products' => $topProducts
        ];
    }

    public function settingsData(): array
    {
        return [
            'settings' => [
                'store_name' => 'Small Ecommerce',
                'store_email' => 'info@smallecommerce.co.ke',
                'store_phone' => '0741400006',
                'store_address' => 'Desai Road, Ngara, Nairobi'
            ]
        ];
    }

    public function resetUserPassword(ServerRequestInterface $request):void
    {        
        if ($request->getMethod() == 'POST') {
            try {
                $email = $request->getParsedBody()['email'];

                if ($user = User::where('email', $email)->first()) {
                    $user->password_hash = password_hash('password123', PASSWORD_DEFAULT);
                    $user->save();
                    $_SESSION['success'] = "Password reset for `" . $user->first_name . "` has been successful.";
                }
            } catch (Exception $e) {
                throw new ValidationException("An error occured", ['error' => "Some error occured while ressetting password". $e->getMessage()]);
            }
        }
    }

    public function editCategoryData(int $categoryId):array
    {
        if (!$category= Category::with(['parent', 'children'])->where('id', $categoryId)->first()) {            
            throw new ValidationException("Product Not Found", ['error' => 'Product not found']);
        }
        return [
            'category' => $category,
            'parent_categories' => $this->productsService->parentCategories()
        ];
    }
}