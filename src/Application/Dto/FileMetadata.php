<?php

declare(strict_types=1);

namespace TelegramGateway\Application\Dto;

use TelegramGateway\Storage\UploadMethod;

/**
 * Everything the application knows about an uploaded file after
 * validation and metadata extraction: its sanitized name, real extension,
 * server-detected MIME type, size, and the Telegram upload method it was
 * classified into.
 */
final readonly class FileMetadata
{
    public function __construct(
        public string $filename,
        public string $extension,
        public string $mimeType,
        public int $size,
        public UploadMethod $uploadMethod,
    ) {
    }
}
