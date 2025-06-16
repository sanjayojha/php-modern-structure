<?php

use App\Controller\HomeController;

use App\Middleware\AuthMiddleware; // Import AuthMiddleware
use App\Middleware\LoggingMiddleware; // Import LoggingMiddleware if needed for specific routes

return function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', [HomeController::class, 'index']);
    $r->addRoute('GET', '/about', [HomeController::class, 'about']);
    $r->addRoute('GET', '/hello/{name}', [HomeController::class, 'hello']);
    $r->addRoute('GET', '/user/{id:\d+}', [HomeController::class, 'userDetail']); // New route for user detail
    $r->addRoute('GET', '/admin', [HomeController::class, 'admin', [AuthMiddleware::class]]);
    $r->addRoute('GET', '/secret-report', [
        HomeController::class,
        'secretReport',
        [AuthMiddleware::class, LoggingMiddleware::class] // Applies these two specific middleware
    ]);
};
