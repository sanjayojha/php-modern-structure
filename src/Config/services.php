<?php

use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use App\Service\MailerService;
use App\Repository\UserRepository;
use App\Controller\HomeController;

// PSR-7 and PSR-17 factories
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

// Middleware
use App\Middleware\TrailingSlashMiddleware;
use App\Middleware\LoggingMiddleware;
use App\Middleware\AuthMiddleware;

return [

    // --- PSR-7/PSR-17 Factories (needed for middleware) ---
    RequestFactoryInterface::class => DI\create(Psr17Factory::class),
    ResponseFactoryInterface::class => DI\create(Psr17Factory::class),
    ServerRequestFactoryInterface::class => DI\create(Psr17Factory::class),
    StreamFactoryInterface::class => DI\create(Psr17Factory::class),
    UploadedFileFactoryInterface::class => DI\create(Psr17Factory::class),
    UriFactoryInterface::class => DI\create(Psr17Factory::class),

    // ServerRequestCreator for converting globals to PSR-7 request
    ServerRequestCreator::class => DI\factory(function (ContainerInterface $c) {
        return new ServerRequestCreator(
            $c->get(ServerRequestFactoryInterface::class),
            $c->get(UriFactoryInterface::class),
            $c->get(UploadedFileFactoryInterface::class),
            $c->get(StreamFactoryInterface::class)
        );
    }),

    // PDO Database Connection
    PDO::class => DI\factory(function (ContainerInterface $c) {
        $dbHost = $_ENV['DB_HOST'];
        $dbPort = $_ENV['DB_PORT'];
        $dbName = $_ENV['DB_NAME'];
        $dbUser = $_ENV['DB_USER'];
        $dbPass = $_ENV['DB_PASS'];

        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $dbUser, $dbPass, $options);
    }),

    // User Repository
    UserRepository::class => DI\autowire(), // Autowire, it needs PDO which is defined above
    // Twig Template Engine
    Environment::class => DI\factory(function (ContainerInterface $c) {
        $loader = new FilesystemLoader(__DIR__ . '/../App/View');
        // Cache directory for Twig (optional, but good for performance in production)
        $cacheDir = __DIR__ . '/../../var/cache/twig';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        return new Environment($loader, [
            // 'cache' => $cacheDir, // Uncomment in production
            'debug' => DEBUG_MODE, // Set to false in production
        ]);
    }),

    // Monolog Logger
    LoggerInterface::class => DI\factory(function (ContainerInterface $c) {
        $log = new Logger('app');
        $logFile = __DIR__ . '/../../var/log/app.log';
        $log->pushHandler(new StreamHandler($logFile, Logger::DEBUG)); // Log everything to file
        return $log;
    }),

    // Example Service
    MailerService::class => DI\autowire(), // PHP-DI can automatically resolve dependencies for simple cases

    // --- Middleware Definitions ---
    TrailingSlashMiddleware::class => DI\autowire(), // No constructor dependencies
    LoggingMiddleware::class => DI\autowire(),       // Autowires LoggerInterface
    AuthMiddleware::class => DI\autowire(),          // No constructor dependencies


    // Controllers
    // You can also define specific dependencies for controllers if autowiring isn't enough
    HomeController::class => DI\autowire(),
];
