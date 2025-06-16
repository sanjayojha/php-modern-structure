<?php

declare(strict_types=1);

namespace App;

use DI\Container;
use FastRoute\Dispatcher;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use App\Handler\ErrorHandler;
use App\Exception\NotFoundException;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response;

// A simple handler that wraps the final controller call
class ControllerHandler implements RequestHandlerInterface
{
    private Container $container;
    private string $controllerClass;
    private string $methodName;
    private array $vars;

    public function __construct(Container $container, string $controllerClass, string $methodName, array $vars)
    {
        $this->container = $container;
        $this->controllerClass = $controllerClass;
        $this->methodName = $methodName;
        $this->vars = $vars;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Here, we can pass the modified request from previous middleware to the controller
        // For simplicity, our controllers still just use route vars and don't take RequestInterface
        // If your controllers need the request object, you'd pass it here:
        // $controller = $this->container->get($this->controllerClass);
        // $responseContent = $controller->{$this->methodName}($request, $this->vars);

        $controller = $this->container->get($this->controllerClass);
        $responseContent = $controller->{$this->methodName}($this->vars); // Old signature for simplicity

        return new Response(200, [], $responseContent);
    }
}


class Kernel implements RequestHandlerInterface
{
    private Container $container;
    private Dispatcher $routerDispatcher;
    private ErrorHandler $errorHandler;
    private bool $debugMode;
    private array $globalMiddlewareStack = [];
    private int $globalMiddlewareIndex = 0; // For tracking position in the global stack

    public function __construct(
        Container $container,
        Dispatcher $routerDispatcher,
        ErrorHandler $errorHandler,
        bool $debugMode,
        array $globalMiddlewareClasses // Global middleware classes
    ) {
        $this->container = $container;
        $this->routerDispatcher = $routerDispatcher;
        $this->errorHandler = $errorHandler;
        $this->debugMode = $debugMode;

        // Instantiate global middleware
        foreach ($globalMiddlewareClasses as $middlewareClass) {
            if ($this->container->has($middlewareClass)) {
                $middleware = $this->container->get($middlewareClass);
                if ($middleware instanceof MiddlewareInterface) {
                    $this->globalMiddlewareStack[] = $middleware;
                } else {
                    $this->container->get(LoggerInterface::class)->error("Global middleware class {$middlewareClass} is not a valid MiddlewareInterface.");
                }
            } else {
                $this->container->get(LoggerInterface::class)->error("Global middleware class {$middlewareClass} not found in DI container.");
            }
        }
    }

    public function boot(): void
    {
        $this->errorHandler->register();
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        // Start processing the request through the global middleware stack
        // The Kernel itself acts as the "next" handler for the global stack,
        // which will then initiate the route-specific dispatch.
        return $this->handle($request);
    }

    /**
     * Implements Psr\Http\Server\RequestHandlerInterface
     * This method is called by a middleware to pass the request to the next handler.
     * When all GLOBAL middleware are exhausted, it then proceeds to route dispatch
     * and route-specific middleware.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (isset($this->globalMiddlewareStack[$this->globalMiddlewareIndex])) {
            $middleware = $this->globalMiddlewareStack[$this->globalMiddlewareIndex];
            $this->globalMiddlewareIndex++; // Move to the next global middleware
            return $middleware->process($request, $this);
        }

        // If no more global middleware, proceed to routing and route-specific dispatch
        return $this->dispatchApplicationRequest($request);
    }

    /**
     * Dispatches the request to the appropriate controller and processes route-specific middleware.
     */
    private function dispatchApplicationRequest(ServerRequestInterface $request): ResponseInterface
    {
        $httpMethod = $request->getMethod();
        $uri = $request->getUri()->getPath();

        $routeInfo = $this->routerDispatcher->dispatch($httpMethod, $uri);

        try {
            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                    throw new NotFoundException();
                case Dispatcher::METHOD_NOT_ALLOWED:
                    $allowedMethods = $routeInfo[1];
                    return new Response(405, ['Allow' => implode(', ', $allowedMethods)], '405 Method Not Allowed');
                case Dispatcher::FOUND:
                    // Extract handler and variables from route info
                    $handlerDefinition = $routeInfo[1]; // This now includes controller, method, AND middleware
                    $vars = $routeInfo[2];

                    // Parse the handler definition: [controller_class, method_name, [middleware_classes...]]
                    if (!is_array($handlerDefinition) || count($handlerDefinition) < 2) {
                        throw new \RuntimeException("Invalid route handler definition.");
                    }

                    $controllerClass = $handlerDefinition[0];
                    $methodName = $handlerDefinition[1];
                    $routeMiddlewareClasses = $handlerDefinition[2] ?? []; // Optional route-specific middleware

                    if (!$this->container->has($controllerClass) || !method_exists($this->container->get($controllerClass), $methodName)) {
                        throw new \RuntimeException("Route handler '{$controllerClass}::{$methodName}' not found or not configured in DI.");
                    }

                    // Build the specific handler for the controller action
                    $controllerHandler = new ControllerHandler($this->container, $controllerClass, $methodName, $vars);


                    // Create a mini-pipeline for route-specific middleware
                    $middlewareChain = $this->buildMiddlewareChain($routeMiddlewareClasses, $controllerHandler);
                    return $middlewareChain->handle($request);
            }
        } catch (\Throwable $e) {
            // Let the registered error handler catch this.
            throw $e;
        }

        // Fallback: return a 500 Internal Server Error if no other path was taken
        return new Response(500, [], '500 Internal Server Error');
    }

    /**
     * Helper to build a chain of PSR-15 middleware with a final handler.
     */
    private function buildMiddlewareChain(array $middlewareClasses, RequestHandlerInterface $finalHandler): RequestHandlerInterface
    {
        // Start from the innermost handler (the controller)
        $currentHandler = $finalHandler;

        // Iterate through middleware in reverse order to build the chain
        // The last middleware added will be the first one to process the request
        foreach (array_reverse($middlewareClasses) as $middlewareClass) {
            if ($this->container->has($middlewareClass)) {
                $middleware = $this->container->get($middlewareClass);
                if ($middleware instanceof MiddlewareInterface) {
                    // Wrap the current handler with the new middleware
                    $currentHandler = new class($middleware, $currentHandler) implements RequestHandlerInterface {
                        private MiddlewareInterface $middleware;
                        private RequestHandlerInterface $nextHandler;

                        public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $nextHandler)
                        {
                            $this->middleware = $middleware;
                            $this->nextHandler = $nextHandler;
                        }

                        public function handle(ServerRequestInterface $request): ResponseInterface
                        {
                            return $this->middleware->process($request, $this->nextHandler);
                        }
                    };
                } else {
                    $this->container->get(LoggerInterface::class)->error("Class {$middlewareClass} is not a valid MiddlewareInterface in route-specific chain.");
                    // In a real app, you might want to stop the request or throw an exception here.
                }
            } else {
                $this->container->get(LoggerInterface::class)->error("Middleware class {$middlewareClass} not found in DI container for route-specific chain.");
            }
        }
        return $currentHandler;
    }
}
