<?php

namespace App\Services;

use Carbon\Carbon;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ServerRequestInterface;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use App\Models\Setting;

class SocialAuthService
{
    /**
     * HTTP client reused by Apple & Facebook token flows
     */
    protected $httpClient;

    public function __construct()
    {
        // Shared HTTP client for Apple / Facebook token exchange
        $this->httpClient = new Client();
    }

    /**
     * Get Google authorization URL
     */
    public function getGoogleAuthorizationUrl(): string
    {
        $cfg    = Setting::getOAuthConfig('google');
        $provider = new Google([
            'clientId'     => $cfg['client_id'],
            'clientSecret' => $cfg['client_secret'],
            'redirectUri'  => $cfg['redirect_uri'],
        ]);

        $authorizationUrl = $provider->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $provider->getState();
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

        $cfg = Setting::getOAuthConfig('google');

        try {
            $provider = new Google([
                'clientId'     => $cfg['client_id'],
                'clientSecret' => $cfg['client_secret'],
                'redirectUri'  => $cfg['redirect_uri'],
            ]);

            $code         = $request->getAttribute('code');
            $accessToken  = $provider->getAccessToken('authorization_code', ['code' => $code]);
            $resourceOwner= $provider->getResourceOwner($accessToken);

            $email     = $resourceOwner->toArray()['email']     ?? '';
            $firstName = $resourceOwner->toArray()['given_name'] ?? '';
            $lastName  = $resourceOwner->toArray()['family_name']?? '';
            $avatar    = $resourceOwner->toArray()['picture']   ?? '';

            $user  = $this->findOrCreateUser($email, $firstName, $lastName, $avatar, 'google');
            $this->loginUser($user);

            return [
                'status'   => 'success',
                'user'     => $user,
                'redirect' => $this->getRedirectUrl(),
            ];
        } catch (IdentityProviderException $e) {
            throw new Exception('Failed to fetch user details from Google: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Google authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Get Apple authorization URL
     */
    public function getAppleAuthorizationUrl(): string
    {
        $cfg    = Setting::getOAuthConfig('apple');
        $state  = bin2hex(random_bytes(16));
        $_SESSION['oauth2state_apple'] = $state;

        $params = [
            'client_id'     => $cfg['client_id'],
            'redirect_uri'  => $cfg['redirect_uri'],
            'response_type' => 'code',
            'scope'         => 'name email',
            'state'         => $state,
            'response_mode' => 'form_post',
        ];

        return 'https://appleid.apple.com/auth/authorize?' . http_build_query($params);
    }

    /**
     * Handle Apple callback
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

        $cfg = Setting::getOAuthConfig('apple');

        $tokenUrl   = 'https://appleid.apple.com/auth/token';
        $tokenParams = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $cfg['redirect_uri'],
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
        ];

        try {
            $response = $this->httpClient->post($tokenUrl, [
                'form_params' => $tokenParams
            ]);
            $tokenData = json_decode($response->getBody(), true);

            if (!isset($tokenData['id_token'])) {
                throw new Exception('No ID token in Apple response');
            }

            $idToken = $tokenData['id_token'];
            $payload = $this->getJwtPayload($idToken);

            $email     = $payload['email']     ?? null;
            $firstName = $payload['given_name'] ?? '';
            $lastName  = $payload['family_name'] ?? '';

            if (!$email) {
                throw new Exception('Apple did not return email address. Please check your Apple ID settings.');
            }

            $user  = $this->findOrCreateUser($email, $firstName, $lastName, null, 'apple');
            $this->loginUser($user);

            return [
                'status'   => 'success',
                'user'     => $user,
                'redirect' => $this->getRedirectUrl(),
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
     * Get Facebook authorization URL (OAuth 2.0 / Graph API)
     */
    public function getFacebookAuthorizationUrl(): string
    {
        $cfg    = Setting::getOAuthConfig('facebook');
        $state  = bin2hex(random_bytes(16));
        $_SESSION['oauth2state_facebook'] = $state;

        $params = [
            'client_id'     => $cfg['client_id'],
            'redirect_uri'  => $cfg['redirect_uri'],
            'response_type' => 'code',
            'scope'         => 'email,public_profile',
            'state'         => $state,
        ];

        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }

    /**
     * Handle Facebook callback and exchange code for access token + user profile
     */
    public function handleFacebookCallback(ServerRequestInterface $request): array
    {
        $state = $_SESSION['oauth2state_facebook'] ?? null;
        unset($_SESSION['oauth2state_facebook']);

        if (empty($request->getAttribute('state')) || ($request->getAttribute('state') !== $state)) {
            throw new Exception('Invalid state parameter for Facebook');
        }

        $code = $request->getAttribute('code');
        if (!$code) {
            throw new Exception('No authorization code provided by Facebook');
        }

        $cfg = Setting::getOAuthConfig('facebook');

        try {
            $tokenResponse = $this->httpClient->post('https://graph.facebook.com/v18.0/oauth/access_token', [
                'form_params' => [
                    'client_id'     => $cfg['client_id'],
                    'redirect_uri'  => $cfg['redirect_uri'],
                    'client_secret' => $cfg['client_secret'],
                    'code'          => $code,
                ],
            ]);
            $tokenData = json_decode($tokenResponse->getBody(), true);

            if (empty($tokenData['access_token'])) {
                throw new Exception('No access token in Facebook response');
            }

            $accessToken = $tokenData['access_token'];

            $profileResponse = $this->httpClient->get('https://graph.facebook.com/me', [
                'query' => [
                    'access_token' => $accessToken,
                    'fields'       => 'id,email,first_name,last_name,picture',
                ],
            ]);
            $profileData = json_decode($profileResponse->getBody(), true);

            $email     = $profileData['email']     ?? null;
            $firstName = $profileData['first_name'] ?? '';
            $lastName  = $profileData['last_name']  ?? '';

            if (!$email) {
                throw new Exception('Facebook did not return an email address.');
            }

            $avatar = $profileData['picture']['data']['url'] ?? null;

            $user  = $this->findOrCreateUser($email, $firstName, $lastName, $avatar, 'facebook');
            $this->loginUser($user);

            return [
                'status'  => 'success',
                'user'    => $user,
                'redirect'=> $this->getRedirectUrl(),
            ];
        } catch (Exception $e) {
            throw new Exception('Facebook authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Build the list of available social login providers
     */
    public function getSocialProviders(): array
    {
        return [
            'google' => [
                'label' => 'Google',
                'url'   => $this->getGoogleAuthorizationUrl(),
                'icon'  => "data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3e%3cpath fill='%234285F4' d='M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z'/%3e%3cpath fill='%2334A853' d='M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z'/%3e%3cpath fill='%23FBBC05' d='M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z'/%3e%3cpath fill='%23EA4335' d='M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z'/%3e%3c/svg%3e",
                'bgClass'    => 'bg-white dark:bg-gray-700',
                'borderClass'=> 'border border-gray-200 dark:border-gray-500',
                'textClass'  => 'text-gray-700 dark:text-gray-200',
                'hoverClass' => 'hover:bg-gray-50 dark:hover:bg-gray-600',
            ],
            'apple' => [
                'label' => 'Apple',
                'url'   => $this->getAppleAuthorizationUrl(),
                'icon'  => "data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3e%3cpath fill='%23ffffff' d='M17.05 20.28c-.98.95-2.05.88-3.08.4c-1.09-.5-2.08-.96-3.23-1.45a12.84 12.84 0 0 1-1.55-.82c-.85-.64-1.55-1.37-2.15-2.18c-.6-.81-.7-1.8-.73-2.78c-.02-.95.08-1.88.3-2.78c.22-.89.59-1.74 1.07-2.52c.48-.79 1.08-1.5 1.76-2.12c.68-.62 1.43-1.13 2.24-1.51c.81-.38 1.69-.55 2.6-.5c.9.05 1.75.26 2.5.63c.75.37 1.41.87 1.96 1.49A14.1 14.1 0 0 1 13.8 12.3c.56.7 1 1.5 1.28 2.38c.28.88.34 1.82.19 2.77c-.16.95-.55 1.85-1.15 2.65a6.8 6.8 0 0 1-2.34 1.73l.27.15zM13 8.35c.27-.32.52-.66.74-1.03c.22-.37.4-.76.55-1.17c.15-.41.25-.84.3-1.29l-.57.01c-.05.39-.12.79-.22 1.19l-.7 2.5a8.3 8.3 0 0 0-.1.79z'/%3e%3c/svg%3e",
                'bgClass'    => 'bg-black dark:bg-gray-900',
                'borderClass'=> 'border border-gray-700 dark:border-gray-500',
                'textClass'  => 'text-white',
                'hoverClass' => 'hover:bg-gray-800 dark:hover:bg-gray-700',
            ],
            'facebook' => [
                'label' => 'Facebook',
                'url'   => $this->getFacebookAuthorizationUrl(),
                'icon'  => "data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3e%3cpath fill='%231877F2' d='M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07c0 6.02 4.39 11.05 10.13 11.93v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.79-4.68 4.53-4.68 1.31 0 2.68.24 2.68.24v2.95h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.32l-.53 3.49h-2.8V24C19.62 23.13 24 18.09 24 12.07z'/%3e%3c/svg%3e",
                'bgClass'    => 'bg-[#1877F2]',
                'borderClass'=> 'border border-[#1877F2]',
                'textClass'  => 'text-white',
                'hoverClass' => 'hover:bg-[#166fe5]',
            ],
        ];
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