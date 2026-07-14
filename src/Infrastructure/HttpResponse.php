<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

/**
 * Result of an HttpClient call. A non-null $transportError means the
 * request never reached the server (DNS/timeout/TLS/etc.); a null
 * $transportError with a non-2xx $statusCode means the server responded
 * but rejected the request — callers must check both.
 */
final readonly class HttpResponse
{
    public function __construct(
        public int $statusCode,
        public string $body,
        public ?string $transportError,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->transportError === null && $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
