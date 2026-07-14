<?php

declare(strict_types=1);

namespace TelegramGateway\Application\Dto;

use DateTimeImmutable;
use TelegramGateway\Storage\UploadMethod;

/**
 * The single, consistent representation of a successful upload, regardless
 * of which Telegram endpoint produced it. The Web UI and REST API both
 * consume this shape only — never Telegram's endpoint-specific payloads.
 *
 * @param array<string, mixed> $telegramResponse
 */
final readonly class NormalizedUploadData
{
    public function __construct(
        public string $fileId,
        public string $fileUniqueId,
        public int $fileSize,
        public int $messageId,
        public string $chatId,
        public UploadMethod $uploadMethod,
        public string $originalFilename,
        public string $originalMimeType,
        public array $telegramResponse,
        public DateTimeImmutable $uploadedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'file_id' => $this->fileId,
            'file_unique_id' => $this->fileUniqueId,
            'file_size' => $this->fileSize,
            'message_id' => $this->messageId,
            'chat_id' => $this->chatId,
            'upload_method' => $this->uploadMethod->telegramApiMethod(),
            'original_filename' => $this->originalFilename,
            'original_mime_type' => $this->originalMimeType,
            'uploaded_at' => $this->uploadedAt->format(DATE_ATOM),
            'telegram_response' => $this->telegramResponse,
        ];
    }
}
