<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response; // Using Nyholm's PSR-7 Response
use Nyholm\Psr7\Factory\Psr17Factory; // For creating PSR-7 responses

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Simple dummy check: require 'X-Auth-Token: secret' header for /admin path
        //if (str_starts_with($request->getUri()->getPath(), '/admin')) {
        $authToken = $request->getHeaderLine('X-Auth-Token');

        if ($authToken !== 'secret') {
            $psr17Factory = new Psr17Factory();
            return (new Response())
                ->withStatus(401) // Unauthorized
                ->withHeader('WWW-Authenticate', 'Bearer realm="Protected Area"')
                ->withHeader('Content-Type', 'text/plain')
                ->withBody($psr17Factory->createStream('Unauthorized. Missing or invalid X-Auth-Token.'));
        }
        //}

        // If not protected or authenticated, pass to the next handler
        return $handler->handle($request);
    }
}
