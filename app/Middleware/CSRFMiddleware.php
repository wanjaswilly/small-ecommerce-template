<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpBadRequestException;

class CSRFMiddleware implements MiddlewareInterface
{
    private $sessionKey = 'csrf_token';
    private $headerName = 'X-CSRF-TOKEN';
    private $formFieldName = '_token';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        # Ensure session is started
        $this->ensureSessionStarted();

        # Skip CSRF for safe methods
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            # Generate token for forms if it doesn't exist
            if (empty($_SESSION[$this->sessionKey])) {
                $this->generateToken();
            }
            return $handler->handle($request);
        }

        # Validate token for unsafe methods
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            throw new HttpBadRequestException($request, 'CSRF token not provided');
        }

        if (!$this->isValidToken($token)) {
            throw new HttpBadRequestException($request, 'Invalid CSRF token');
        }

        # Regenerate token after successful validation (optional but recommended)
        $this->generateToken();
        
        $response = $handler->handle($request);
        return $this->attachTokenToResponse($response);
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        # Check header first
        $token = $request->getHeaderLine($this->headerName);

        if (!empty($token)) {
            return $token;
        }

        # Check body for form submissions
        $body = $request->getParsedBody();

        if (is_array($body) && isset($body[$this->formFieldName])) {
            return $body[$this->formFieldName];
        }

        # Check query parameters (less common)
        $queryParams = $request->getQueryParams();
        if (isset($queryParams[$this->formFieldName])) {
            return $queryParams[$this->formFieldName];
        }

        return null;
    }

    private function isValidToken(string $token): bool
    {
        $storedToken = $_SESSION[$this->sessionKey] ?? null;

        if (!$storedToken) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[$this->sessionKey] = $token;
        return $token;
    }

    public function getToken(): string
    {
        $this->ensureSessionStarted();

        if (empty($_SESSION[$this->sessionKey])) {
            return $this->generateToken();
        }

        return $_SESSION[$this->sessionKey];
    }

    public static function verifyToken(string $token): bool
    {
        $self = new self();
        $self->ensureSessionStarted();
        return $self->isValidToken($token);
    }

    private function attachTokenToResponse(ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader(
            $this->headerName,
            $_SESSION[$this->sessionKey]
        );
    }
}
