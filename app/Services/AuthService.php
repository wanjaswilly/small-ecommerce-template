<?php

namespace App\Services;


use App\Exceptions\ValidationException;
use App\Models\Setting;
use App\Models\User;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthService
{
    public function login(ServerRequestInterface $request): string
    {

        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $remember = isset($data['remember']);
        $redirectTo = $data['redirect_to'] ?? '/';

        # Validate input
        if (empty($email) || empty($password)) {
            throw new ValidationException('Incomplete Form', ['Incomplete form' => "Please enter both email and password"]);
        }

        # Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new ValidationException('Wrong Credentials', ['Auth error' => "Invalid email or incorresct password"]);
        }

        # Verify password
        if (!password_verify($password, $user->password_hash)) {
            $_SESSION['error'] = 'Invalid email or password';
            throw new ValidationException('Wrong Credentials', ['Auth error' => "Invalid email or incorrect password"]);
        }

        # Check if user is active
        if (!$user->is_active) {
            throw new ValidationException('Account Deactivated', ['Auth error' => 'Your account has been deactivated. Please contact support.']);
        }

        # Login successful - set session
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['login_time'] = time();
        $_SESSION['user'] = $user;

        # Handle "remember me" functionality
        if ($remember) {
            $this->setRememberToken($user);
        }

        # Log the login
        $this->logLogin($user, $request);

        $_SESSION['success'] = 'Welcome back, ' . $user->name . '!';

        return $this->redirectToAppropriatePage($redirectTo);

    }

    public function register(ServerRequestInterface $request): string
    {

        $data = $request->getParsedBody();

        $first_name = trim($data['first_name'] ?? '');
        $last_name = trim($data['last_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirmation = $data['password_confirmation'] ?? '';
        $phone = trim($data['phone'] ?? '');
        $agreeTerms = isset($data['agree_terms']);
        $redirectTo = $data['redirect_to'] ?? '/';

        # Validate required fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($phone)) {
            throw new ValidationException('Incomplete Form', ['Incomplete form' => 'Please fill in all required fields.']);
        }

        # Validate terms agreement
        if (!$agreeTerms) {
            throw new ValidationException('Terms Agreement', ['Agree terms' => 'You must agree to the terms and conditions']);
        }

        # Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid Email', ['Invalid email' => 'Please enter a valid email address']);
        }

        # Validate password strength
        if (strlen($password) < 8) {
            $_SESSION['error'] = '';
            throw new ValidationException('Short Password', ['Short password' => 'Password must be at least 8 characters long']);
        }

        # Validate password confirmation
        if ($password !== $passwordConfirmation) {
            throw new ValidationException('Password Mismatch', ['Password Mismatch' => 'Your passwords do not match']);
        }

        # Validate phone number (Kenyan format)
        $phone = $this->formatPhoneNumber($phone);
        if (!$this->isValidPhoneNumber($phone)) {
            throw new ValidationException('Invalid Phone Number', ['Invalid Number' => 'Please enter a valid Kenyan phone number']);
        }

        # Check if email already exists
        if (User::where('email', $email)->exists()) {
            throw new ValidationException('Email Exists', ['Email exists' => 'An account with this email already exists']);
        }

        # Check if phone number already exists
        if (User::where('phone', $phone)->exists()) {
            throw new ValidationException('Phone Number Exists', ['Phone exists' => 'An account with this phone number already exists']);
        }

        try {
            # Create new user
            $user = User::create([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'customer', # Default role
                'is_active' => true,
                'email_verified_at' => null, # Will need verification
            ]);

            # Auto-login the user after registration
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
            $_SESSION['user_role'] = $user->role;
            $_SESSION['login_time'] = time();

            # Log the registration
            $this->logRegistration($user, $request);

            $_SESSION['success'] = 'Account created successfully! Welcome to ' . (Setting::getValue('store_name') ?? 'our store') . '!';

            # Redirect to appropriate page
            return $this->redirectToAppropriatePage( $redirectTo);

        } catch (\Exception $e) {
            throw new ValidationException('Registration Failed', ['Registration error' => 'Registration failed. Please try again. ' . $e->getMessage()]);
        }

    }

    public function logout(): void
    {
        
        # Clear remember token if exists
        if (isset($_SESSION['user_id'])) {
            $user = User::find($_SESSION['user_id']);
            if ($user) {
                $user->remember_token = null;
                $user->save();
            }
        }

        # Destroy session
        session_destroy();

        # Clear session cookie
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

        $_SESSION['success'] = 'You have been logged out successfully.';
    }

    /**
     * Redirect user to appropriate page based on role and previous location
     */
    private function redirectToAppropriatePage(string $redirectTo = '/'): string
    {
        $userRole = $_SESSION['user_role'] ?? 'customer';

        # If user was trying to access a specific page, redirect there
        if ($redirectTo && $redirectTo !== '/' && !$this->isAuthPage($redirectTo)) {
            return $redirectTo;
        }

        # Redirect based on role
        switch ($userRole) {
            case 'admin':
            case 'super_admin':
                return '/admin/dashboard';

            case 'rider':
                return '/rider/dashboard';
            case 'staff':
                return '/staff/dashboard';

            case 'customer':
                return '/user/account/dashboard';
            default:
                return '/';
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

        # Set cookie for 30 days
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

        # You can save this to a login_logs table or just log it
        error_log("User login: {$user->email} from IP: {$ip}");

        # Update last login time
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

        # You could send a welcome email here
        # $this->sendWelcomeEmail($user);
    }

    /**
     * Format phone number to Kenyan format
     */
    private function formatPhoneNumber(string $phone): string
    {
        # Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        # Convert to 254 format if it's in local format
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
        # Kenyan phone numbers: 2547XXXXXXXX
        return preg_match('/^2547\d{8}$/', $phone) === 1;
    }

    /**
     * Auto-login from remember token - this is called by javascript function from frontend with a remember token 
     * @param ServerRequestInterface $request - request with the remember token
     * 
     * @return array [status {http status code}, message{string message}]
     */
    public function autoLoginFromRememberToken(ServerRequestInterface $request): array
    {
        # Check if remember token exists
        $rememberToken = $_COOKIE['remember_token'] ?? null;

        if ($rememberToken) {
            $user = User::where('remember_token', $rememberToken)->first();

            # if ($user && password_verify($rememberToken, $user->remember_token)) {
            if ($user) {
                # Login user
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_role'] = $user->role;
                $_SESSION['login_time'] = time();

                # Update last login
                $user->last_login_at = date('Y-m-d H:i:s');
                $user->last_login_ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
                $user->save();
            } else {
                # Invalid token, clear cookie
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }

        return [
            'status' => 200,
            'message' => $user->name . " Logged in successfully",
        ];
    }

}