<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\SocialAuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use App\Models\User;
use App\Models\Setting;
use Psr\Http\Server\RequestHandlerInterface;

class AuthController extends BaseController
{
    private AuthService $authService;
    private SocialAuthService $socialAuthService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->socialAuthService = new SocialAuthService();
    }

    /**
     * Show login form
     */
    public function showLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'pages/login.twig', [
            'redirect_to'  => $request->getQueryParams()['redirect'] ?? '/',
            'socialProviders' => $this->socialAuthService->getSocialProviders(),
        ]);
    }

    /**
     * Show registration form
     */
    public function showRegister(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'pages/register.twig', [
            'redirect_to'  => $request->getQueryParams()['redirect'] ?? '/',
            'socialProviders' => $this->socialAuthService->getSocialProviders(),
        ]);
    }

    /**
     * Process login form
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->redirect($response, $this->authService->login($request));

    }

    /**
     * Process registration form
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->redirect($response, $this->authService->register($request));
    }

    /**
     * Logout user
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->authService->logout();
        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }

    /**
     * Redirect to Google for authentication
     */
    public function googleLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $url = $this->socialAuthService->getGoogleAuthorizationUrl();
        return $response->withHeader('Location', $url)->withStatus(302);
    }

    /**
     * Handle Google callback
     */
    public function googleCallback(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $result = $this->socialAuthService->handleGoogleCallback($request);
            if ($result['status'] === 'success') {
                // Store user in session (already done in service)
                $_SESSION['success'] = 'Welcome back, ' . $result['user']->first_name . '!';
                return $response->withHeader('Location', $result['redirect'])->withStatus(302);
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    /**
     * Redirect to Apple for authentication
     */
    public function appleLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $url = $this->socialAuthService->getAppleAuthorizationUrl();
        return $response->withHeader('Location', $url)->withStatus(302);
    }

    /**
     * Handle Apple callback
     */
    public function appleCallback(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $result = $this->socialAuthService->handleAppleCallback($request);
            if ($result['status'] === 'success') {
                // Store user in session (already done in service)
                $_SESSION['success'] = 'Welcome back, ' . $result['user']->first_name . '!';
                return $response->withHeader('Location', $result['redirect'])->withStatus(302);
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
