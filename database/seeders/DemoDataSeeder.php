<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Models\Coupon;
use App\Models\OrderItem;
use App\Models\ShippingZone;
use App\Models\TaxRate;

class DemoDataSeeder
{
    public function run()
    {
        echo "🌱 Starting demo data seeding...\n";

        // Clear existing demo data first
        echo "🧹 Clearing existing demo data...\n";
        $this->clearExistingData();

        // Seed categories
        echo "📂 Seeding categories...\n";
        $this->seedCategories();

        // Seed users
        echo "👥 Seeding users...\n";
        $this->seedUsers();

        // Seed products
        echo "📦 Seeding products...\n";
        $this->seedProducts();

        // Seed coupons
        echo "🎫 Seeding coupons...\n";
        $this->seedCoupons();

        // Seed orders
        echo "📋 Seeding orders...\n";
        $this->seedOrders();

        // Seed reviews
        echo "⭐ Seeding reviews...\n";
        $this->seedReviews();

        // Seed shipping and tax data
        echo "🚚 Seeding shipping zones and tax rates...\n";
        $this->seedShippingAndTax();

        // Seed pages
        echo "📄 Seeding pages...\n";
        $this->seedPages();

        echo "✅ Demo data seeding completed!\n";
    }

    private function clearExistingData()
    {
        // Clear tables in reverse dependency order using delete instead of truncate
        Capsule::table('reviews')->delete();
        Capsule::table('order_items')->delete();
        Capsule::table('orders')->delete();
        Capsule::table('products')->delete();
        Capsule::table('categories')->delete();
        Capsule::table('coupons')->delete();
        Capsule::table('newslettersubscribers')->delete();

        // Keep existing users but clear demo users
        Capsule::table('users')->where('email', '!=', 'admin@example.com')->delete();

        echo "   → Cleared existing demo data\n";
    }

    private function seedCategories()
    {
        require_once __DIR__ . '/../factories/CategoryFactory.php';

        // Clear existing categories
        Capsule::table('categories')->truncate();

        $categories = [];
        $slugs = [];
        $i = 0;
        while ($i < 5) {
            $cate = \Database\Factories\CategoryFactory::make();
            if (!in_array($cate['slug'], $slugs, true)) {
                $categories[] = $cate;
                $slugs[] = $cate['slug'];
                $i++;

            }
        }

        foreach ($categories as $category) {
            Category::create($category);
        }

        echo "   → Created " . count($categories) . " categories\n";
    }

    private function seedUsers()
    {
        require_once __DIR__ . '/../factories/UserFactory.php';

        // Clear existing users (except any existing admin)
        Capsule::table('users')->where('email', '!=', 'admin@example.com')->delete();



        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $userData = \Database\Factories\UserFactory::make();

            // Ensure one admin user
            if ($i === 0) {
                $userData['email'] = 'admin@example.com';
                $userData['first_name'] = 'Admin';
                $userData['last_name'] = 'User';
                $userData['role'] = 'admin';
                $userData['is_active'] = true;
            }

            $users[] = User::create($userData);
        }

        echo "   → Created " . count($users) . " users (including 1 admin)\n";
    }

    private function seedProducts()
    {
        require_once __DIR__ . '/../factories/ProductFactory.php';

        // Clear existing products
        Capsule::table('products')->truncate();

        $categories = Category::all();
        $products = [];

        for ($i = 0; $i < 25; $i++) {
            $productData = \Database\Factories\ProductFactory::make();
            $productData['category_id'] = $categories->random()->id;
            $products[] = Product::create($productData);
        }

        echo "   → Created " . count($products) . " products across " . $categories->count() . " categories\n";
    }

    private function seedCoupons()
    {
        require_once __DIR__ . '/../factories/CouponFactory.php';

        // Clear existing coupons
        Capsule::table('coupons')->truncate();

        $coupons = [];
        for ($i = 0; $i < 3; $i++) {
            $coupons[] = Coupon::create(\Database\Factories\CouponFactory::make());
        }

        echo "   → Created " . count($coupons) . " coupons\n";
    }

    private function seedOrders()
    {
        require_once __DIR__ . '/../factories/OrderFactory.php';

        // Clear existing orders and order items
        Capsule::table('order_items')->truncate();
        Capsule::table('orders')->truncate();

        $users = User::where('role', 'customer')->get();
        $products = Product::all();

        $orders = [];
        for ($i = 0; $i < 10; $i++) {
            $orderData = \Database\Factories\OrderFactory::make();
            $orderData['user_id'] = $users->random()->id;
            $order = Order::create($orderData);

            // Add 1-5 random items to each order
            $itemCount = rand(1, 5);
            $orderItems = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $quantity = rand(1, 3);

                $orderItems[] = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $quantity,
                ]);
            }

            $orders[] = $order;
        }

        echo "   → Created " . count($orders) . " orders with order items\n";
    }

    private function seedReviews()
    {
        require_once __DIR__ . '/../factories/ReviewFactory.php';

        // Clear existing reviews
        Capsule::table('reviews')->truncate();

        $users = User::where('role', 'customer')->get();
        $products = Product::all();

        $reviews = [];
        for ($i = 0; $i < 20; $i++) {
            $reviewData = \Database\Factories\ReviewFactory::make();
            $reviewData['user_id'] = $users->random()->id;
            $reviewData['product_id'] = $products->random()->id;
            $reviews[] = Review::create($reviewData);
        }

        echo "   → Created " . count($reviews) . " product reviews\n";
    }

    private function seedShippingAndTax()
    {
        // Seed shipping zones
        Capsule::table('shipping_zones')->truncate();
        foreach (ShippingZone::getDefaultZones() as $zone) {
            ShippingZone::create($zone);
        }

        // Seed tax rates
        Capsule::table('tax_rates')->truncate();
        foreach (TaxRate::getDefaultRates() as $rate) {
            TaxRate::create($rate);
        }

        echo "   → Created " . count(ShippingZone::getDefaultZones()) . " shipping zones\n";
        echo "   → Created " . count(TaxRate::getDefaultRates()) . " tax rates\n";
    }

    private function seedPages()
    {
        Capsule::table('pages')->truncate();
        foreach (\App\Models\Page::getDefaultPages() as $page) {
            \App\Models\Page::create($page);
        }
        echo "   → Created " . count(\App\Models\Page::getDefaultPages()) . " pages\n";
    }
}