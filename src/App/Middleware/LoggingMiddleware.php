<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->info(sprintf(
            'Incoming Request: %s %s from %s',
            $request->getMethod(),
            $request->getUri()->getPath(),
            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ));

        $response = $handler->handle($request); // Pass to the next middleware or final handler

        $this->logger->info(sprintf(
            'Outgoing Response Status: %d for %s %s',
            $response->getStatusCode(),
            $request->getMethod(),
            $request->getUri()->getPath()
        ));

        return $response;
    }
}
