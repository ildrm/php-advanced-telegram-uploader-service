<?php

declare(strict_types=1);

namespace TelegramGateway\Storage\Dto;

use TelegramGateway\Storage\UploadMethod;

/**
 * Everything a storage driver needs to upload one file, decided entirely
 * by the Application layer before the Storage layer is called.
 */
final readonly class StorableFile
{
    public function __construct(
        public string $filePath,
        public string $filename,
        public string $mimeType,
        public UploadMethod $uploadMethod,
        public int $fileSize,
    ) {
    }
}
