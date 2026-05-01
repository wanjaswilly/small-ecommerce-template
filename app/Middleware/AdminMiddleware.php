<?php

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Models\User;

class AdminMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = ['Please log in to access this page.'];
            $_SESSION['intended_url'] = (string) $request->getUri();
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $user = User::find($_SESSION['user_id']);
        if (!$user || !$user->isAdmin()) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write('Forbidden - Admin access required');
            return $response->withStatus(403);
        }

        return $handler->handle($request);
    }
}