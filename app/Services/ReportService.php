<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;

class ReportService
{
    public function getDailySales(string $start, string $end): array
    {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        $sales = Order::selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return $sales;
    }

    public function getTopProducts(int $limit = 10): array
    {
        return OrderItem::selectRaw('product_id, products.name, SUM(order_items.quantity) as total_quantity, SUM(order_items.total_price) as total_revenue')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('product_id', 'products.name')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getCategorySales(): array
    {
        return OrderItem::selectRaw('categories.name as category_name, SUM(order_items.quantity) as total_quantity, SUM(order_items.total_price) as total_revenue')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->toArray();
    }

    public function getSalesSummary(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $todaySales = Order::whereDate('created_at', $today)->where('status', '!=', 'cancelled')->sum('total_amount');
        $thisMonthSales = Order::where('created_at', '>=', $thisMonth)->where('status', '!=', 'cancelled')->sum('total_amount');
        $lastMonthSales = Order::where('created_at', '>=', $lastMonth)->where('created_at', '<', $thisMonth)->where('status', '!=', 'cancelled')->sum('total_amount');

        return [
            'today_sales' => (float) $todaySales,
            'this_month_sales' => (float) $thisMonthSales,
            'last_month_sales' => (float) $lastMonthSales,
            'total_orders' => Order::where('status', '!=', 'cancelled')->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'completed_orders' => Order::where('status', 'delivered')->count(),
            'conversion_rate' => $this->calculateConversionRate(),
            'avg_order_value' => $this->calculateAvgOrderValue(),
        ];
    }

    public function getSalesMetrics(): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        return [
            'sales_today' => (float) Order::whereDate('created_at', $today)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'sales_yesterday' => (float) Order::whereDate('created_at', $yesterday)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'sales_30_days' => (float) Order::where('created_at', '>=', $thirtyDaysAgo)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'orders_today' => Order::whereDate('created_at', $today)->count(),
            'orders_30_days' => Order::where('created_at', '>=', $thirtyDaysAgo)->count(),
            'new_customers' => User::where('role', 'customer')->where('created_at', '>=', $thirtyDaysAgo)->count(),
            'top_categories' => $this->getCategorySales(),
            'low_stock_products' => Product::lowStock()->limit(5)->get(['id', 'name', 'stock_quantity']),
        ];
    }

    public function getOrderMetrics(): array
    {
        return [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'confirmed_orders' => Order::where('status', 'confirmed')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::whereIn('status', ['shipped', 'out_for_delivery'])->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'paid_orders' => Order::where('payment_status', 'paid')->count(),
            'unpaid_orders' => Order::where('payment_status', 'pending')->count(),
        ];
    }

    protected function calculateConversionRate(): float
    {
        $totalOrders = Order::where('status', '!=', 'cancelled')->count();
        $totalUsers = User::where('role', 'customer')->count();
        return $totalUsers > 0 ? round(($totalOrders / $totalUsers) * 100, 2) : 0;
    }

    protected function calculateAvgOrderValue(): float
    {
        return round(Order::where('status', '!=', 'cancelled')->avg('total_amount') ?: 0, 2);
    }
}