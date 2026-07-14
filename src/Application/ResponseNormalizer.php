<?php

declare(strict_types=1);

namespace TelegramGateway\Application;

use DateTimeImmutable;
use TelegramGateway\Application\Dto\FileMetadata;
use TelegramGateway\Application\Dto\NormalizedUploadData;
use TelegramGateway\Infrastructure\Clock;
use TelegramGateway\Storage\Dto\StorageResponse;
use TelegramGateway\Storage\UploadMethod;

/**
 * Telegram returns a different payload shape per send* method (sendPhoto
 * returns an array of PhotoSize objects; sendDocument/sendVideo/sendAudio/
 * sendVoice each return a single object). This class is the one place that
 * understands those differences and converts any of them into the single
 * NormalizedUploadData shape the rest of the application consumes.
 */
final class ResponseNormalizer
{
    public function __construct(private readonly Clock $clock)
    {
    }

    public function normalize(StorageResponse $response, FileMetadata $metadata, string $fallbackChatId): NormalizedUploadData
    {
        $result = $response->telegramResult;
        [$fileId, $fileUniqueId, $fileSize] = $this->extractFileIdentifiers($response->uploadMethod, $result);

        $chat = $result['chat'] ?? [];
        $chatId = is_array($chat) && isset($chat['id']) ? (string) $chat['id'] : $fallbackChatId;

        return new NormalizedUploadData(
            fileId: $fileId,
            fileUniqueId: $fileUniqueId,
            fileSize: $fileSize ?? $metadata->size,
            messageId: (int) ($result['message_id'] ?? 0),
            chatId: $chatId,
            uploadMethod: $response->uploadMethod,
            originalFilename: $metadata->filename,
            originalMimeType: $metadata->mimeType,
            telegramResponse: $result,
            uploadedAt: $this->clock->now(),
        );
    }

    /**
     * @param array<string, mixed> $result
     * @return array{0: string, 1: string, 2: int|null}
     */
    private function extractFileIdentifiers(UploadMethod $method, array $result): array
    {
        if ($method === UploadMethod::Photo) {
            return $this->extractFromPhotoSizes(is_array($result['photo'] ?? null) ? $result['photo'] : []);
        }

        $field = $method->multipartFieldName();
        $object = is_array($result[$field] ?? null) ? $result[$field] : [];

        return $this->extractFromSingleObject($object);
    }

    /**
     * @param array<string, mixed> $object
     * @return array{0: string, 1: string, 2: int|null}
     */
    private function extractFromSingleObject(array $object): array
    {
        return [
            (string) ($object['file_id'] ?? ''),
            (string) ($object['file_unique_id'] ?? ''),
            isset($object['file_size']) ? (int) $object['file_size'] : null,
        ];
    }

    /**
     * @param list<array<string, mixed>> $sizes
     * @return array{0: string, 1: string, 2: int|null}
     */
    private function extractFromPhotoSizes(array $sizes): array
    {
        // Telegram returns PhotoSize entries smallest-first; the largest
        // (highest-resolution) rendition is the most useful one to expose.
        $largest = $sizes !== [] ? $sizes[count($sizes) - 1] : [];

        return $this->extractFromSingleObject(is_array($largest) ? $largest : []);
    }
}
