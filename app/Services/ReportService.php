<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
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

        return [
            'today_sales' => Order::whereDate('created_at', $today)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'this_month_sales' => Order::where('created_at', '>=', $thisMonth)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'last_month_sales' => Order::where('created_at', '>=', $lastMonth)->where('created_at', '<', $thisMonth)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_orders' => Order::where('status', '!=', 'cancelled')->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'completed_orders' => Order::where('status', 'delivered')->count(),
        ];
    }
}