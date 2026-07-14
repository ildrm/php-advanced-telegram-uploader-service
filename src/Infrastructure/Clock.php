<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

use DateTimeImmutable;

/**
 * Thin wrapper around "now" so components that need the current time
 * (Logger, response normalization) depend on an injectable service instead
 * of calling `new DateTimeImmutable()` directly throughout the codebase.
 */
final class Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
