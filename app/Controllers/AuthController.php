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
        return $this->render($request, $response, 'pages/login.twig', [
            'redirect_to' => $request->getQueryParams()['redirect'] ?? '/'
        ]);
    }

    /**
     * Show registration form
     */
    public function showRegister(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'pages/register.twig', [
            'redirect_to' => $request->getQueryParams()['redirect'] ?? '/'
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

}