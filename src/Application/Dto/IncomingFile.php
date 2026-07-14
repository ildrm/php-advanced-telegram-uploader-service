<?php

declare(strict_types=1);

namespace TelegramGateway\Application\Dto;

/**
 * A raw, not-yet-validated file as received from the HTTP layer. Keeps the
 * Application layer free of $_FILES/superglobal details — the Presentation
 * layer is responsible for building this from the actual request.
 */
final readonly class IncomingFile
{
    public function __construct(
        public string $tmpPath,
        public string $originalName,
        public string $reportedMimeType,
        public int $size,
        public int $uploadError,
    ) {
    }
}
