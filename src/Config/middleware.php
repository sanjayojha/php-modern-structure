<?php

// Define the order of global middleware classes here.
// These will be processed from top to bottom.
return [
    App\Middleware\TrailingSlashMiddleware::class,
    // App\Middleware\LoggingMiddleware::class,
    // App\Middleware\AuthMiddleware::class,
    // Add other global middleware here
];
