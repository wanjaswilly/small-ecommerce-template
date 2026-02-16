<?php

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        # Skip session for static resources
        if ($this->isStaticResource($path)) {
            return $handler->handle($request);
        }

        # Start the session
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_samesite' => 'Strict'
            ]);
        }

        # create session error and success arrays if not existing
        $_SESSION['error'] = [];
        $_SESSION['success'] = [];

        # Regenerate session ID periodically for security
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 3600) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }

        $response = $handler->handle($request);

        # Ensure session is written
        session_write_close();

        return $response;
    }

    /**
     * Check if the request is for a static resource
     */
    private function isStaticResource(string $path): bool
    {
        $staticExtensions = [
            'css',
            'js',
            'png',
            'jpg',
            'jpeg',
            'gif',
            'svg',
            'ico',
            'woff',
            'woff2',
            'ttf',
            'eot',
            'map',
            'webp',
            'avif'
        ];

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), $staticExtensions);
    }
}
