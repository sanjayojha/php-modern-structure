<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Log\LoggerInterface;
use Twig\Environment;
use App\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface; // Import ResponseInterface
use Nyholm\Psr7\Response;             // Using Nyholm's PSR-7 Response

class ErrorHandler
{
    private LoggerInterface $logger;
    private Environment $twig;
    private bool $debugMode;

    public function __construct(LoggerInterface $logger, Environment $twig, bool $debugMode = false)
    {
        $this->logger = $logger;
        $this->twig = $twig;
        $this->debugMode = $debugMode;
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $this->logger->error("PHP Error: [{$severity}] {$message} in {$file} on line {$line}");

        if (in_array($severity, [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
            $this->renderErrorPage(500, "An internal server error occurred.");
            exit(1); // Exit on fatal errors
        }

        return true;
    }

    public function handleException(\Throwable $exception): void
    {
        $statusCode = 500;
        $message = 'An unexpected error occurred.';

        if ($exception instanceof NotFoundException) {
            $statusCode = $exception->getCode();
            $message = $exception->getMessage();
        } elseif ($exception->getCode() >= 400 && $exception->getCode() < 600) {
            $statusCode = $exception->getCode();
            $message = $exception->getMessage();
        }

        $this->logger->critical("Uncaught Exception: {$exception->getMessage()} in {$exception->getFile()} on line {$exception->getLine()}", [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString()
        ]);

        $this->renderErrorPage($statusCode, $message, $exception);
        // Note: We don't return ResponseInterface here because this handler directly outputs and exits
        // as it's the last resort for uncaught exceptions.
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR])) {
            $this->logger->critical("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
            $this->renderErrorPage(500, "A critical error occurred that prevented the application from running.");
        }
    }

    private function renderErrorPage(int $statusCode, string $publicMessage, ?\Throwable $exception = null): void
    {
        http_response_code($statusCode);

        $errorMessage = $this->debugMode ? $exception?->getMessage() : $publicMessage;
        $errorTrace = $this->debugMode ? $exception?->getTraceAsString() : null;

        try {
            echo $this->twig->render('error.html.twig', [
                'statusCode' => $statusCode,
                'publicMessage' => $publicMessage,
                'errorMessage' => $errorMessage,
                'errorTrace' => $errorTrace,
                'debugMode' => $this->debugMode,
            ]);
        } catch (\Throwable $e) {
            echo "<h1>Error {$statusCode}</h1>";
            echo "<p>{$publicMessage}</p>";
            if ($this->debugMode && $exception) {
                echo "<p>Detailed error: {$exception->getMessage()}</p>";
                echo "<pre>{$exception->getTraceAsString()}</pre>";
            }
            $this->logger->error("Failed to render error page: {$e->getMessage()}");
        }
    }
}
