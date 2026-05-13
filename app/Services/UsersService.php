<?php

namespace App\Services;

use App\Exceptions\UnAuthenticatedAccessException;
use App\Exceptions\ValidationException;
use App\Models\Favourite;
use App\Models\Order;
use App\Models\User;
use Exception;
use Psr\Http\Message\ServerRequestInterface;


class UsersService
{
    private int $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'];
    }

    public function dashboardData(): array
    {

        return [
            'orders' => Order::where('user_id', $this->userId)
                ->latest()
                ->limit(5)
                ->get()
                ->toArray(),
            'total_orders' => Order::where('user_id', $this->userId)->count(),
            'completed_orders' => Order::where('user_id', $this->userId)
                ->where('status', 'delivered')
                ->count(),
            # 'wishlists' => 
        ];
    }

    public function userOrdersData(): array
    {
        return [
            'orders' => Order::where('user_id', $this->userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray(),
        ];
    }

    public function singleOrderData($orderId): array
    {

        $order = Order::where('id', $orderId)
            ->where('user_id', $this->userId)
            ->first();

        if (!$order) {
            # throw model instance not found exception

        }
        return [
            'order' => $order,
        ];
    }

    public function user()
    {
        $user = User::find($this->userId);

        if (!$user) {
            # User not found (shouldn't happen if session exists)
            session_destroy();
            # throw unathenticated access exception
            throw new UnAuthenticatedAccessException("Kindly login to continue");
        }
        return ['user' => $user->toArray()];
    }

    public function updateProfile(ServerRequestInterface $request): void
    {
        $data = $request->getParsedBody();
        $user = User::find($this->userId);

        if (!$user) {
            throw new ValidationException('User Not Found', ['Invalid user' => 'User with that email or id not found']);
        }

        try {
            # Update basic information
            $user->first_name = $data['first_name'] ?? $user->first_name;
            $user->last_name = $data['last_name'] ?? $user->last_name;
            $user->email = $data['email'] ?? $user->email;
            $user->phone_number = $data['phone'] ?? $user->phone;

            # Update password if provided
            $currentPassword = $data['current_password'] ?? '';
            $newPassword = $data['new_password'] ?? '';
            $confirmPassword = $data['new_password_confirmation'] ?? '';

            if (!empty($currentPassword) && !empty($newPassword)) {
                # Verify current password
                if (!password_verify($currentPassword, $user->password)) {
                    throw new ValidationException('Incorrect Password', ['Incorrect Password' => 'Incorrect current passwords entered']);
                }

                # Validate new password
                if ($newPassword !== $confirmPassword) {
                    throw new ValidationException('Password Mismatch', ['Password Mismatch' => 'Your passwords do not match']);
                }

                if (strlen($newPassword) < 8) {
                    throw new ValidationException('Short Password', ['Short password' => 'Password must be at least 8 characters long']);
                }

                $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $user->save();

            # Update session data
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_email'] = $user->email;

            $_SESSION['success'] = 'Profile updated successfully';
        } catch (\Exception $e) {
            throw new ValidationException('Profile Update Failed', ['Update error' => 'Failed to update profile. Please try again. :' . $e->getMessage()]);
        }
    }

    public function wishlistData(): array
    {
        $user = User::with(['favourites', 'favourites.product'])->find($this->userId);

        if (!$user) {
            # User not found (shouldn't happen if session exists)
            session_destroy();
            throw new UnAuthenticatedAccessException("Kindly login to view your favorite products");
        }

        return ['user' => $user];
    }

    # AddToWishlist
    public function AddToWishlist(ServerRequestInterface $request): array
    {
        $data = $request->getParsedBody();

        # check if user is logged in
        if (!$this->userId || empty($this->userId)) {
            return [
                'status' => 400,
                'message' => ['error' => 'Kindly login to add this product to your favourite']
            ];
        }

        if (!User::find($_SESSION['user_id'])) {
            return [
                'status' => 400,
                'message' => ['error' => 'Kindly login or refresh to add this product to your favourite']
            ];
        }

        if (Favourite::where('product_id', $data['product_id'])->where('user_id', $_SESSION['user_id'])->first()) {
            return [
                'status' => 200,
                'message' => ['sucess' => 'Product already in your wishlist']
            ];
        }

        $favourite = Favourite::create([
            'product_id' => $data['product_id'],
            'user_id' => $_SESSION['user_id']
        ]);

        if ($favourite) {
            return [
                'status' => 200,
                'message' => ['success' => 'Product added to your wishlist']
            ];
        }

        return [
            'status' => 400,
            'message' => ['error' => 'Some error occurred while adding product to your favourite']
        ];
    }

    public function removeFromWishlist(int $favouriteId): array
    {
        # check if user is logged in
        if (!$_SESSION['user_id'] || empty($_SESSION['user_id'])) {
            throw new UnAuthenticatedAccessException("You are not logged in", ['error' => 'Kindly login to manage your favourites']);
        }
        if (!$favourite = Favourite::find($favouriteId)) {
            throw new ValidationException("Product Not In your Wishlist", ['error' => 'This product is not in your favourites']);
        }

        if ($favourite->delete()) {
            return [
                'status' => 200,
                'message' => ['success' => "Product removed from Wishlist"]
            ];
        }

        throw new Exception("Error removing product from your Wishlist");
    }

    public function clearWishlist(): array
    {
        if (!$favourites = Favourite::where('user_id', $_SESSION['user_id'])->get()) {
            return ['error' => "Product not on your Wishlist"];
        }

        if ($favourites->delete()) {
            return [
                'status' => 400,
                'message' => ['success' => "Product removed from Wishlist"]
            ];
        }
        return [
            'status' => 400,
            'message' => ['error' => "Error removing product from your Wishlist"]
        ];
    }

}