<?php

namespace App\Controllers;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class BaseController
{
    protected function view(Request $request): Twig
    {
        return Twig::fromRequest($request);
    }

    protected function render(
        Request $request,
        Response $response,
        string $template,
        array $data = []
    ): Response {
        return $this->view($request)->render($response, $template, $data);
    }

    protected function redirect(Response $response, string $url, int $status = 302): Response
    {
        return $response->withHeader('Location', $url)->withStatus($status);
    }

    protected function back(Request $request, Response $response): Response
    {
        $referer = $request->getHeaderLine('Referer') ?: '/';
        return $this->redirect($response, $referer);
    }

    protected function json(Response $response, mixed $data, int $status = 200): Response
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}