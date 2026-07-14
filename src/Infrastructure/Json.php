<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

use JsonException;

/**
 * Thin wrapper around json_encode/json_decode that always throws on
 * failure instead of returning false/null, so callers cannot accidentally
 * treat a failed encode/decode as an empty result.
 */
final class Json
{
    /**
     * @throws JsonException
     */
    public static function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     * @throws JsonException
     */
    public static function decode(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new JsonException('Decoded JSON payload was not an array.');
        }

        return $decoded;
    }
}
