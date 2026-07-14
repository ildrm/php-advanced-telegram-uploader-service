<?php

declare(strict_types=1);

namespace TelegramGateway\Storage\Dto;

use TelegramGateway\Storage\UploadMethod;

/**
 * The raw, not-yet-normalized result of a successful store() call: which
 * method was used, and the "result" object Telegram returned for it.
 * Everything outside the Storage layer, other than the Application layer's
 * ResponseNormalizer, must never read $telegramResult directly.
 *
 * @param array<string, mixed> $telegramResult
 */
final readonly class StorageResponse
{
    public function __construct(
        public UploadMethod $uploadMethod,
        public array $telegramResult,
    ) {
    }
}
