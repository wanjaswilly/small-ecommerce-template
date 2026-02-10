<?php

namespace App\Services;

use App\Exceptions\UnAuthenticatedAccessException;
use App\Exceptions\ValidationException;
use App\Models\User;
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
                ->latests()
                ->orderBy('created_at', 'desc')
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
}