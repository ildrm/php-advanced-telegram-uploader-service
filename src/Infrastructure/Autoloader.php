<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

/**
 * Minimal PSR-4-style autoloader for the TelegramGateway\ namespace.
 *
 * The project intentionally avoids Composer so it can be deployed to shared
 * hosting by uploading files alone. This is the only file that must be
 * loaded manually (via require) before autoloading becomes available.
 */
final class Autoloader
{
    private const NAMESPACE_PREFIX = 'TelegramGateway\\';

    public static function register(string $sourceDirectory): void
    {
        $sourceDirectory = rtrim($sourceDirectory, '/\\');

        spl_autoload_register(static function (string $class) use ($sourceDirectory): void {
            if (!str_starts_with($class, self::NAMESPACE_PREFIX)) {
                return;
            }

            $relativeClass = substr($class, strlen(self::NAMESPACE_PREFIX));
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
            $path = $sourceDirectory . DIRECTORY_SEPARATOR . $relativePath;

            if (is_file($path)) {
                require $path;
            }
        });
    }
}
