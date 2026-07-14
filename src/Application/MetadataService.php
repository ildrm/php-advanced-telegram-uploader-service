<?php

declare(strict_types=1);

namespace TelegramGateway\Application;

use TelegramGateway\Application\Dto\FileMetadata;
use TelegramGateway\Application\Dto\IncomingFile;
use TelegramGateway\Infrastructure\FileInfo;
use TelegramGateway\Storage\UploadMethod;

/**
 * Extracts metadata from a (previously validated) uploaded file and
 * decides which Telegram upload method it should be sent through, based on
 * its real, server-detected MIME type and extension. Falls back to
 * sendDocument whenever the category cannot be determined confidently.
 */
final class MetadataService
{
    /** @var array<string, UploadMethod> extension => method, for cases MIME type alone cannot resolve */
    private const EXTENSION_OVERRIDES = [
        'oga' => UploadMethod::Voice,
        'ogg' => UploadMethod::Voice,
    ];

    public function extract(IncomingFile $file): FileMetadata
    {
        $filename = $this->sanitizeFilename($file->originalName);
        $extension = FileInfo::extensionFromFilename($filename);
        $mimeType = FileInfo::detectMimeType($file->tmpPath);

        return new FileMetadata(
            filename: $filename,
            extension: $extension,
            mimeType: $mimeType,
            size: $file->size,
            uploadMethod: $this->resolveUploadMethod($extension, $mimeType),
        );
    }

    private function resolveUploadMethod(string $extension, string $mimeType): UploadMethod
    {
        if (isset(self::EXTENSION_OVERRIDES[$extension])) {
            return self::EXTENSION_OVERRIDES[$extension];
        }

        return match (true) {
            str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml' => UploadMethod::Photo,
            str_starts_with($mimeType, 'video/') => UploadMethod::Video,
            str_starts_with($mimeType, 'audio/') => UploadMethod::Audio,
            default => UploadMethod::Document,
        };
    }

    /**
     * Strips any directory component and control characters from a
     * client-supplied filename, since it is later used both in a log entry
     * and in the multipart Content-Disposition sent to Telegram.
     */
    private function sanitizeFilename(string $originalName): string
    {
        $name = basename($originalName);
        $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name) ?? '';
        $name = trim($name);

        return $name !== '' ? $name : 'file';
    }
}
