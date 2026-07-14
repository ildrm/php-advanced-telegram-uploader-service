<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

/**
 * Low-level, technical file inspection helpers. This class only reports
 * facts about a file (its real MIME type, its extension); it never applies
 * business rules about which files are allowed — that belongs to the
 * Application layer's ValidationService and MetadataService.
 */
final class FileInfo
{
    /**
     * Detects the MIME type from the file's actual contents (not the
     * client-supplied Content-Type header, which cannot be trusted).
     */
    public static function detectMimeType(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return 'application/octet-stream';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return 'application/octet-stream';
        }

        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mimeType !== false && $mimeType !== '' ? $mimeType : 'application/octet-stream';
    }

    /**
     * Returns the lower-case extension of a filename without the leading
     * dot, or an empty string when the filename has no extension.
     */
    public static function extensionFromFilename(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    public static function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) max(0, $bytes);
        $unitIndex = 0;

        while ($size >= 1024.0 && $unitIndex < count($units) - 1) {
            $size /= 1024.0;
            $unitIndex++;
        }

        $decimals = $unitIndex === 0 ? 0 : 2;

        return number_format($size, $decimals) . ' ' . $units[$unitIndex];
    }
}
