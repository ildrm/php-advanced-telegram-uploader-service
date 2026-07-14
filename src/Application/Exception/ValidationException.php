<?php

declare(strict_types=1);

namespace TelegramGateway\Application\Exception;

use RuntimeException;

/**
 * Thrown when an uploaded file fails validation before ever reaching the
 * Storage layer. The message is always safe to show to the client.
 */
final class ValidationException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
