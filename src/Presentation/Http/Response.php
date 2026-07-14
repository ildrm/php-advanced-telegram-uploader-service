<?php

declare(strict_types=1);

namespace TelegramGateway\Presentation\Http;

use TelegramGateway\Infrastructure\Json;

/**
 * Builds the two JSON response shapes used everywhere in the REST API:
 * { success, request_id, data } and { success, request_id, error }.
 * No other response shape is ever produced.
 */
final class Response
{
    /**
     * @param array<string, mixed> $data
     */
    public function success(string $requestId, array $data, int $status = 200): never
    {
        $this->json([
            'success' => true,
            'request_id' => $requestId,
            'data' => $data,
        ], $status);
    }

    public function error(string $requestId, string $code, string $message, int $status = 400): never
    {
        $this->json([
            'success' => false,
            'request_id' => $requestId,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $status): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }

        echo Json::encode($payload);
        exit;
    }
}
