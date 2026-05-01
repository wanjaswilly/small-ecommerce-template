<?php

use App\Exceptions\ValidationException;
use App\Middleware\TrackStatsMiddleware;
use App\Models\SiteStat;
use Slim\Views\Twig;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;
use Slim\Exception\HttpNotFoundException;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Exception\HttpInternalServerErrorException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\TwigFilter;
use Twig\TwigFunction;

require __DIR__ . '/vendor/autoload.php';

session_start();

$app = AppFactory::create();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load configuration files
$config = require __DIR__ . '/config/app.php';
$dbConfig = require __DIR__ . '/config/database.php';

// Initialize Eloquent ORM
$capsule = new Capsule;
$capsule->addConnection($dbConfig['connections'][$dbConfig['default']]);

// Make Eloquent available globally (optional)
$capsule->setAsGlobal();

// Boot Eloquent
$capsule->bootEloquent();

/*
 * Stats tracking moved to middleware for proper $request context.
 * See App\Middleware\TrackStatsMiddleware.
 */

$twig = Twig::create(__DIR__ . '/templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));


$baseAssetUrl = $_ENV['ASSET_BASE'];

$twig->getEnvironment()->addFunction(new TwigFunction('asset', function ($path) use ($baseAssetUrl) {
    return '/' . ltrim($path, '/');
}));

foreach (
    [
        'APP_URL',
        'APP_NAME',
        'ASSET_BASE',
        'APP_ENVIRONMENT'
    ] as $key
) {
    $twig->getEnvironment()->addGlobal($key, $_ENV[$key] ?? '');
}


$twig->getEnvironment()->addFilter(
    new TwigFilter('json_decode', function ($value) {
        // Already an array → return as-is
        if (is_array($value)) return $value;

        // Non-string (int, bool, null) → return as-is
        if (!is_string($value)) return $value; 

        // Try JSON decode
        $decoded = json_decode($value, true);

        // If valid JSON → return decoded array
        if (json_last_error() === JSON_ERROR_NONE) return $decoded; 

        // Not JSON → return original string
        return $value;
    })
);

$app->add(new TrackStatsMiddleware());
$app->add(new App\Middleware\CsrfMiddleware());
$app->add(new \App\Middleware\SessionMiddleware());

# add sessions to twig
$twig->getEnvironment()->addGlobal('session', $_SESSION);
$twig->addExtension(new App\Extensions\CsrfExtension());

# Translation service
$translationService = new App\Services\TranslationService();
$twig->getEnvironment()->addFunction(new TwigFunction('__', function (string $key, ?string $locale = null) use ($translationService) {
    return $translationService->get($key, $locale);
}));


$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// 404 Not Found
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) use ($app) {
    $view = Twig::fromRequest($request);
    $response = new \Slim\Psr7\Response();

    // In production, show generic message
    $message = $_ENV['APP_ENVIRONMENT'] === 'DEV' ? $exception : $exception->getMessage();

    return $view->render($response->withStatus(404), 'errors/404.twig', ['message' => $message]);
});

$errorMiddleware->setErrorHandler(ValidationException::class, function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) {

    $response = new \Slim\Psr7\Response();

    # store errors & old data in session
    $_SESSION['errors'] = $exception->errors ?? null;
    $_SESSION['old'] = $request->getParsedBody() ?? null;

    # redirect back
    $referer = $request->getHeaderLine('Referer') ?: '/';

    return $response
        ->withHeader('Location', $referer)
        ->withStatus(302, $exception->getMessage());
});

$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) use ($twig): Response {
    $response = new \Slim\Psr7\Response();

    // In production, show generic message
    $message = $_ENV['APP_ENVIRONMENT'] === 'DEV' ? $exception : $exception->getMessage();

    return $twig->render($response, 'errors/500.twig', ['message' => $message])->withStatus(500);
});


(require __DIR__ . '/routes/web.php')($app);

return $app;
