<?php

declare(strict_types=1);

namespace TelegramGateway\Storage\Exception;

use RuntimeException;

/**
 * Thrown when Telegram's Bot API explicitly rejects a request (ok: false),
 * e.g. an invalid chat id, an oversized file, or an unreachable bot.
 */
final class TelegramApiException extends RuntimeException
{
    private function __construct(
        string $description,
        private readonly int $telegramErrorCode,
    ) {
        parent::__construct($description);
    }

    public static function fromApiError(int $errorCode, string $description): self
    {
        return new self($description !== '' ? $description : 'Unknown Telegram API error.', $errorCode);
    }

    public function telegramErrorCode(): int
    {
        return $this->telegramErrorCode;
    }
}
