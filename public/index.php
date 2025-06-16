<?php

// public/index.php

// 1. Autoload Composer dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load environment variables from .env file
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// --- Configuration ---
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('DEBUG_MODE', APP_ENV === 'development');

// --- Dependency Injection Container Setup ---
use DI\ContainerBuilder;
use function FastRoute\simpleDispatcher;
use App\Kernel;
use App\Handler\ErrorHandler;

// PSR-7 and PSR-17 factory for creating request/response objects
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;

$containerBuilder = new ContainerBuilder();
if (!DEBUG_MODE) {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache/php-di');
}
$containerBuilder->addDefinitions(__DIR__ . '/../src/Config/services.php');
$container = $containerBuilder->build();

// --- Error Handling Setup ---
// Get error handler from container
$errorHandler = $container->get(ErrorHandler::class);
$errorHandler->register();

// --- Routing Dispatcher Initialization ---
$routerDispatcher = simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $routes = require __DIR__ . '/../src/Routing/web.php';
    $routes($r);
});

// --- Get Middleware Chain from Config ---
$middlewareClasses = require __DIR__ . '/../src/Config/middleware.php';

// --- Create and Boot the Kernel ---
$kernel = new Kernel($container, $routerDispatcher, $errorHandler, DEBUG_MODE, $middlewareClasses);
$kernel->boot();

// --- Create PSR-7 ServerRequest from PHP Globals ---
$requestCreator = $container->get(ServerRequestCreator::class);
$request = $requestCreator->fromGlobals();

// --- Handle the Request through the Kernel (Middleware + Dispatch) ---
try {
    $response = $kernel->handleRequest($request);
} catch (\Throwable $e) {
    // If an error occurs during middleware or dispatch, the error handler has already
    // taken over and exited (for critical errors) or rendered a page.
    // This catch block is mostly for completeness, as set_exception_handler is the primary catch.
    exit(1);
}

// --- Send the Response ---
// Send HTTP headers
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
// Send status code
http_response_code($response->getStatusCode());
// Send response body
echo $response->getBody();

// Ensure the application exits after sending the response
exit(0);
