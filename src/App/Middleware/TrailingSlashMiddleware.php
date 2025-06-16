<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response; // Using Nyholm's PSR-7 Response

class TrailingSlashMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri()->getPath();

        // If URI has a trailing slash and is not just '/', redirect
        if (strlen($uri) > 1 && substr($uri, -1) === '/') {
            $newUri = rtrim($uri, '/');
            return (new Response())
                ->withStatus(301) // Moved Permanently
                ->withHeader('Location', $newUri);
        }

        return $handler->handle($request);
    }
}
