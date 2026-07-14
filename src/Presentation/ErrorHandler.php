<?php

declare(strict_types=1);

namespace TelegramGateway\Presentation;

use Closure;
use ErrorException;
use TelegramGateway\Infrastructure\Configuration;
use TelegramGateway\Infrastructure\Logger;
use TelegramGateway\Infrastructure\Uuid;
use Throwable;

/**
 * Routes every uncaught exception, PHP warning/notice, and fatal error
 * through one place: log the full detail, then render a safe response
 * through a context-specific renderer (JSON for the API, HTML for the web
 * UI) that never exposes file paths, stack traces, or configuration.
 */
final class ErrorHandler
{
    public static function registerForJson(Logger $logger, Configuration $configuration, Http\Response $response): void
    {
        self::register($logger, $configuration, static function (Throwable $e, string $requestId) use ($response, $configuration): void {
            $message = $configuration->isDebug() ? $e->getMessage() : 'An unexpected error occurred.';
            $response->error($requestId, 'internal_error', $message, 500);
        });
    }

    public static function registerForHtml(Logger $logger, Configuration $configuration): void
    {
        self::register($logger, $configuration, static function (Throwable $e, string $requestId) use ($configuration): void {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
            }

            $message = $configuration->isDebug()
                ? htmlspecialchars($e->getMessage(), ENT_QUOTES)
                : 'An unexpected error occurred. Please try again later.';

            echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
                . '<title>Error</title></head><body>'
                . '<h1>Something went wrong</h1>'
                . "<p>{$message}</p>"
                . '<p>Reference: ' . htmlspecialchars($requestId, ENT_QUOTES) . '</p>'
                . '</body></html>';
        });
    }

    private static function register(Logger $logger, Configuration $configuration, Closure $renderer): void
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e) use ($logger, $renderer): void {
            $requestId = Uuid::v4();

            $logger->error('Uncaught exception', [
                'request_id' => $requestId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $renderer($e, $requestId);
        });

        register_shutdown_function(static function () use ($logger, $renderer): void {
            $error = error_get_last();

            if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                return;
            }

            $requestId = Uuid::v4();

            $logger->error('Fatal error', [
                'request_id' => $requestId,
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]);

            $renderer(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']), $requestId);
        });
    }
}
