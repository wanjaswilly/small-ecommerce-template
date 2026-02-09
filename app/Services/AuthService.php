<?php

namespace App\Services;


use App\Exceptions\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

class AuthService
{
    public function login(ServerRequestInterface $request)
    {

        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $remember = isset($data['remember']);
        $redirectTo = $data['redirect_to'] ?? '/';

        // Validate input
        if (empty($email) || empty($password)) {
            throw new ValidationException('Incomplete Form', ['Incomplete form' => "Please enter both email and password"]);
        }

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new ValidationException('Wrong Credentials', ['Auth error' => "Invalid email or incorresct password"]);
        }

        // Verify password
        if (!password_verify($password, $user->password_hash)) {
            $_SESSION['error'] = 'Invalid email or password';
            throw new ValidationException('Wrong Credentials', ['Auth error' => "Invalid email or incorresct password"]);
        }

        // Check if user is active
        if (!$user->is_active) {
            $_SESSION['error'] = 'Your account has been deactivated. Please contact support.';
            throw new ValidationException('Wrong Credentials', ['Auth error' => "Invalid email or incorresct password"]);

            // Login successful - set session
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
            $_SESSION['user_role'] = $user->role;
            $_SESSION['login_time'] = time();

            // Handle "remember me" functionality
            if ($remember) {
                $this->setRememberToken($user);
            }

            // Log the login
            $this->logLogin($user, $request);

            $_SESSION['success'] = 'Welcome back, ' . $user->name . '!';

            return $this->redirectToAppropriatePage($redirectTo);
        }
    }

    /**
     * Redirect user to appropriate page based on role and previous location
     */
    private function redirectToAppropriatePage(string $redirectTo = '/'): string
    {
        $userRole = $_SESSION['user_role'] ?? 'customer';

        // If user was trying to access a specific page, redirect there
        if ($redirectTo && $redirectTo !== '/' && !$this->isAuthPage($redirectTo)) {
            return  $redirectTo;
        }

        // Redirect based on role
        switch ($userRole) {
            case 'admin':
            case 'super_admin':
                return '/admin/dashboard';

            case 'rider':
                return '/rider/dashboard';
            case 'staff':
                return '/staff/dashboard';

            case 'customer':
            default:
                return '/user/account/dashboard';
        }
    }

    /**
     * Check if the path is an authentication-related page
     */
    private function isAuthPage(string $path): bool
    {
        $authPages = ['/login', '/register', '/forgot-password', '/reset-password'];
        return in_array($path, $authPages);
    }

    /**
     * Set remember token for "remember me" functionality
     */
    private function setRememberToken(User $user): void
    {
        $token = bin2hex(random_bytes(32));
        $user->remember_token = password_hash($token, PASSWORD_DEFAULT);
        $user->save();

        // Set cookie for 30 days
        setcookie(
            'remember_token',
            $token,
            time() + (30 * 24 * 60 * 60),
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
    }

    /**
     * Log login activity
     */
    private function logLogin(User $user, ServerRequestInterface $request): void
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $request->getHeaderLine('User-Agent');

        // You can save this to a login_logs table or just log it
        error_log("User login: {$user->email} from IP: {$ip}");

        // Update last login time
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->last_login_ip = $ip;
        $user->save();
    }

    /**
     * Log registration activity
     */
    private function logRegistration(User $user, ServerRequestInterface $request): void
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $request->getHeaderLine('User-Agent');

        error_log("New user registration: {$user->email} from IP: {$ip}");

        // You could send a welcome email here
        // $this->sendWelcomeEmail($user);
    }

    /**
     * Format phone number to Kenyan format
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        // Convert to 254 format if it's in local format
        if (strlen($phone) === 9 && str_starts_with($phone, '7')) {
            $phone = '254' . $phone;
        } elseif (strlen($phone) === 10 && str_starts_with($phone, '07')) {
            $phone = '254' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Validate Kenyan phone number
     */
    private function isValidPhoneNumber(string $phone): bool
    {
        // Kenyan phone numbers: 2547XXXXXXXX
        return preg_match('/^2547\d{8}$/', $phone) === 1;
    }

    /**
     * Auto-login from remember token
     */
    public function autoLoginFromRememberToken(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Skip if already logged in
        if (isset($_SESSION['user_id'])) {
            return $handler->handle($request);
        }

        // Check if remember token exists
        $rememberToken = $_COOKIE['remember_token'] ?? null;

        if ($rememberToken) {
            $user = User::where('remember_token', $rememberToken)->first();

            // if ($user && password_verify($rememberToken, $user->remember_token)) {
            if ($user) {
                // Login user
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_role'] = $user->role;
                $_SESSION['login_time'] = time();

                // Update last login
                $user->last_login_at = date('Y-m-d H:i:s');
                $user->last_login_ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
                $user->save();
            } else {
                // Invalid token, clear cookie
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }

        return $handler->handle($request);
    }

}