<?php

declare(strict_types=1);

namespace TelegramGateway\Storage;

use TelegramGateway\Storage\Dto\StorableFile;
use TelegramGateway\Storage\Dto\StorageResponse;
use TelegramGateway\Storage\Exception\StorageException;
use TelegramGateway\Storage\Exception\TelegramApiException;

/**
 * Contract for a storage backend the Application layer can upload files
 * to. Version 1 ships a single implementation (TelegramStorage), but the
 * Application layer depends on this abstraction rather than that concrete
 * class so a future storage provider can be added without changing the
 * upload workflow (UploadPipeline, ValidationService, MetadataService).
 */
interface StorageInterface
{
    /**
     * Uploads a file and returns the provider's raw (not yet normalized)
     * response.
     *
     * @throws StorageException on transport failure
     * @throws TelegramApiException when the provider rejects the request
     */
    public function store(StorableFile $file): StorageResponse;

    /**
     * Removes a previously stored file's chat message, revoking access to
     * it in the chat it was posted to.
     *
     * @throws StorageException on transport failure
     */
    public function delete(int $messageId): bool;

    /**
     * Retrieves provider-side metadata for a previously stored file.
     *
     * @return array<string, mixed>
     * @throws StorageException on transport failure
     * @throws TelegramApiException when the provider rejects the request
     */
    public function getMetadata(string $fileId): array;

    /** Verifies the storage backend is reachable and credentials are valid. */
    public function healthCheck(): bool;
}
