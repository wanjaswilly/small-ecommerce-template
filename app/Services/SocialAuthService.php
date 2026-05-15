<?php

namespace App\Services;

use Carbon\Carbon;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ServerRequestInterface;
use App\Models\User;
use Exception;

class SocialAuthService
{
    /**
     * Google provider instance
     */
    protected $googleProvider;

    /**
     * Apple provider instance (we'll implement as generic OAuth2)
     */
    protected $appleProvider;

    public function __construct()
    {
        // Initialize Google provider
        $this->googleProvider = new Google([
            'clientId'     => $_GOOGLE_CLIENT_ID ?? getenv('GOOGLE_CLIENT_ID'),
            'clientSecret' => $_GOOGLE_CLIENT_SECRET ?? getenv('GOOGLE_CLIENT_SECRET'),
            'redirectUri'  => $_GOOGLE_REDIRECT_URI ?? getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/login/google/callback',
        ]);

        // Note: For Apple, we'll use a generic OAuth2 provider since there's no official League provider
        // We'll implement Apple login separately in the handleAppleCallback method
    }

    /**
     * Get Google authorization URL
     */
    public function getGoogleAuthorizationUrl(): string
    {
        $authorizationUrl = $this->googleProvider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $this->googleProvider->getState();
        return $authorizationUrl;
    }

    /**
     * Handle Google callback
     */
    public function handleGoogleCallback(ServerRequestInterface $request): array
    {
        $state = $_SESSION['oauth2state'] ?? null;
        unset($_SESSION['oauth2state']);

        if (empty($request->getAttribute('state')) || ($request->getAttribute('state') !== $state)) {
            throw new Exception('Invalid state parameter');
        }

        try {
            // Get access token
            $accessToken = $this->googleProvider->getAccessToken('authorization_code', [
                'code' => $request->getAttribute('code')
            ]);

            // Get resource owner (user) details
            $resourceOwner = $this->googleProvider->getResourceOwner($accessToken);

            $email = $resourceOwner->toArray()['email'] ?? '';
            $firstName = $resourceOwner->toArray()['given_name'] ?? '';
            $lastName = $resourceOwner->toArray()['family_name'] ?? '';
            $avatar = $resourceOwner->toArray()['picture'] ?? '';

            // Find or create user
            $user = $this->findOrCreateUser($email, $firstName, $lastName, $avatar, 'google');

            // Log in the user
            $this->loginUser($user);

            return [
                'status' => 'success',
                'user' => $user,
                'redirect' => $this->getRedirectUrl()
            ];
        } catch (IdentityProviderException $e) {
            throw new Exception('Failed to fetch user details from Google: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Google authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Get Apple authorization URL
     * Note: Apple requires a different approach - we'll generate the URL manually
     */
    public function getAppleAuthorizationUrl(): string
    {
        $clientId = $_APPLE_CLIENT_ID ?? getenv('APPLE_CLIENT_ID');
        $redirectUri = $_APPLE_REDIRECT_URI ?? getenv('APPLE_REDIRECT_URI') ?: 'http://localhost/login/apple/callback';
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth2state_apple'] = $state;

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'name email',
            'state' => $state,
            // Apple requires a response_mode, we'll use form_post
            'response_mode' => 'form_post'
        ];

        return 'https://appleid.apple.com/auth/authorize?' . http_build_query($params);
    }

    /**
     * Handle Apple callback
     * Note: Apple returns user info in the ID token (JWT) and may only return name once
     */
    public function handleAppleCallback(ServerRequestInterface $request): array
    {
        $state = $_SESSION['oauth2state_apple'] ?? null;
        unset($_SESSION['oauth2state_apple']);

        if (empty($request->getAttribute('state')) || ($request->getAttribute('state') !== $state)) {
            throw new Exception('Invalid state parameter for Apple');
        }

        $code = $request->getAttribute('code');
        if (!$code) {
            throw new Exception('No code provided by Apple');
        }

        // Prepare token request
        $clientId = $_APPLE_CLIENT_ID ?? getenv('APPLE_CLIENT_ID');
        $clientSecret = $_APPLE_CLIENT_SECRET ?? getenv('APPLE_CLIENT_SECRET');
        $redirectUri = $_APPLE_REDIRECT_URI ?? getenv('APPLE_REDIRECT_URI') ?: 'http://localhost/login/apple/callback';

        // For Apple, we need to use client secret JWT or use the secret directly if we have it
        // For simplicity, we'll assume we have a client secret (not the recommended way for production)
        // In production, you should generate a JWT client secret

        $tokenUrl = 'https://appleid.apple.com/auth/token';
        $tokenParams = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];

        // Make HTTP request to get token
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->post($tokenUrl, [
                'form_params' => $tokenParams
            ]);
            $tokenData = json_decode($response->getBody(), true);

            if (!isset($tokenData['id_token'])) {
                throw new Exception('No ID token in Apple response');
            }

            // Decode ID token (JWT) to get user info
            $idToken = $tokenData['id_token'];
            $payload = $this->getJwtPayload($idToken);

            $email = $payload['email'] ?? null;
            // Apple only provides name in the first authentication
            $firstName = $payload['given_name'] ?? '';
            $lastName = $payload['family_name'] ?? '';

            // If email is null (user didn't share email), we cannot create account
            if (!$email) {
                throw new Exception('Apple did not return email address. Please check your Apple ID settings.');
            }

            // Find or create user
            $user = $this->findOrCreateUser($email, $firstName, $lastName, null, 'apple');

            // Log in the user
            $this->loginUser($user);

            return [
                'status' => 'success',
                'user' => $user,
                'redirect' => $this->getRedirectUrl()
            ];
        } catch (Exception $e) {
            throw new Exception('Apple authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Find or create user by email and provider
     */
    protected function findOrCreateUser(string $email, string $firstName, string $lastName, ?string $avatar, string $provider): User
    {
        // Check if user exists by email
        $user = User::where('email', $email)->first();

        if ($user) {
            // Update provider info if needed
            // We could store provider and provider_id in a separate table or in user meta
            return $user;
        }

        // Create new user
        // Generate a random password hash for social login users (they won't use this)
        $randomPassword = bin2hex(random_bytes(16));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);

        $user = User::create([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password_hash' => $passwordHash,
            'phone_number' => null, // Social login users may not have phone number
            'address' => null,
            'city' => null,
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => Carbon::now(), // Consider email verified for social login
        ]);

        // TODO: Store provider and provider_id in a separate table (e.g., social_accounts)

        return $user;
    }

    /**
     * Log in user (set session)
     */
    protected function loginUser(User $user): void
    {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->first_name . ' ' . $user->last_name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['login_time'] = time();
        $_SESSION['user'] = $user;

        // Update last login
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->last_login_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user->save();
    }

    /**
     * Get redirect URL after login
     */
    protected function getRedirectUrl(): string
    {
        $redirectTo = $_SESSION['redirect_to'] ?? '/';
        unset($_SESSION['redirect_to']);

        // If redirect is to an auth page, go to default
        $authPages = ['/login', '/register', '/forgot-password', '/reset-password'];
        if (in_array($redirectTo, $authPages)) {
            return '/user/account/dashboard';
        }

        return $redirectTo;
    }

    /**
     * Extract payload from JWT (without verification - for simplicity)
     * In production, you should verify the JWT with Apple's public key
     */
    protected function getJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT');
        }

        // Decode the payload (middle part)
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode JWT payload');
        }

        return $payload;
    }
}