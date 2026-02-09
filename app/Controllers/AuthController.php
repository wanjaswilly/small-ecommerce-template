<?php

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use App\Models\User;
use App\Models\Setting;
use Psr\Http\Server\RequestHandlerInterface;

class AuthController extends BaseController
{
    private $view;
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Show login form
     */
    public function showLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $view = Twig::fromRequest($request);

        return $view->render($response, 'pages/login.twig', [
            'redirect_to' => $request->getQueryParams()['redirect'] ?? '/'
        ]);
    }

    /**
     * Show registration form
     */
    public function showRegister(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {

        $view = Twig::fromRequest($request);

        return $view->render($response, 'pages/register.twig', [
            'redirect_to' => $request->getQueryParams()['redirect'] ?? '/'
        ]);
    }

    /**
     * Process login form
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->redirect($response, $this->authService->login($request));
        
    }

    /**
     * Process registration form
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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

        // Validate required fields
        if (empty($first_name) ||empty($last_name) || empty($email) || empty($password) || empty($phone)) {
            $_SESSION['error'] = 'Please fill in all required fields';
            $_SESSION['old'] = compact('first_name', 'last_name', 'email', 'phone');
            return $response
                ->withHeader('Location', '/register?redirect=' . urlencode($redirectTo))
                ->withStatus(302);
        }

        // Validate terms agreement
        if (!$agreeTerms) {
            $_SESSION['error'] = 'You must agree to the terms and conditions';
            $_SESSION['old_register'] = compact('first_name', 'last_name', 'email', 'phone');
            return $response
                ->withHeader('Location', '/register?redirect=' . urlencode($redirectTo))
                ->withStatus(302);
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address';
            $_SESSION['old_register'] = compact('first_name', 'last_name', 'email', 'phone');
            return $response
                ->withHeader('Location', '/register?redirect=' . urlencode($redirectTo))
                ->withStatus(302);
        }

        // Validate password strength
        if (strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters long';
            $_SESSION['old_register'] = compact('first_name', 'last_name', 'email', 'phone');
            return $response
                ->withHeader('Location', '/register?redirect=' . urlencode($redirectTo))
                ->withStatus(302);
        }

        // Validate password confirmation
        if ($password !== $passwordConfirmation) {
            $_SESSION['error'] = 'Passwords do not match';
            $_SESSION['old_register'] = compact('first_name', 'last_name', 'email', 'phone');
            return $response
                ->withHeader('Location', '/register?redirect=' . urlencode($redirectTo))
                ->withStatus(302);
        }

        // Validate phone number (Kenyan format)
        $phone = $this->formatPhoneNumber($phone);
        if (!$this->isValidPhoneNumber($phone)) {
            $_SESSION['error'] = 'Please enter a valid Kenyan phone number';
            $_SESSION['old_register'] = compact('first_name', 'last_name', 'email', 'phone');
            return $response
                ->withHeader('Location', '/register?redirect=' . urlencode($redirectTo))
                ->withStatus(302);
        }

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            $_SESSION['error'] = 'An account with this email already exists';
            $_SESSION['old_register'] = compact('first_name', 'last_name', 'email', 'phone');
            return $response
                ->withHeader('Location', '/register?redirect=' . urlencode($redirectTo))
                ->withStatus(302);
        }

        // Check if phone number already exists
        if (User::where('phone', $phone)->exists()) {
            $_SESSION['error'] = 'An account with this phone number already exists';
            $_SESSION['old_register'] = compact('first_name', 'last_name', 'email', 'phone');
            return $response
                ->withHeader('Location', '/register?redirect=' . urlencode($redirectTo))
                ->withStatus(302);
        }

        try {
            // Create new user
            $user = User::create([
                'first_name' => $first_name, 
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'customer', // Default role
                'is_active' => true,
                'email_verified_at' => null, // Will need verification
            ]);

            // Auto-login the user after registration
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
            $_SESSION['user_role'] = $user->role;
            $_SESSION['login_time'] = time();

            // Log the registration
            $this->logRegistration($user, $request);

            $_SESSION['success'] = 'Account created successfully! Welcome to ' . (Setting::getValue('store_name') ?? 'our store') . '!';

            // Redirect to appropriate page
            return $this->redirectToAppropriatePage($response, $redirectTo);

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Registration failed. Please try again.' . $e->getMessage();
            $_SESSION['old_register'] = compact('name', 'email', 'phone');
            return $response
                ->withHeader('Location', '/register?redirect=' . urlencode($redirectTo))
                ->withStatus(302);
        }
    }

    /**
     * Logout user
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Clear remember token if exists
        if (isset($_SESSION['user_id'])) {
            $user = User::find($_SESSION['user_id']);
            if ($user) {
                $user->remember_token = null;
                $user->save();
            }
        }

        // Destroy session
        session_destroy();

        // Clear session cookie
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

        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }

}