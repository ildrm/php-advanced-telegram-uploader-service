<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

use Random\RandomException;

/**
 * Generates RFC 4122 version 4 UUIDs without requiring the ext-uuid
 * extension, used as the request identifier surfaced in logs and API
 * responses so a single upload can be traced end to end.
 */
final class Uuid
{
    /**
     * @throws RandomException
     */
    public static function v4(): string
    {
        $bytes = random_bytes(16);

        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
