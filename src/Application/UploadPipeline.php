<?php

declare(strict_types=1);

namespace TelegramGateway\Application;

use TelegramGateway\Application\Dto\IncomingFile;
use TelegramGateway\Application\Dto\UploadResult;
use TelegramGateway\Application\Exception\ValidationException;
use TelegramGateway\Infrastructure\Configuration;
use TelegramGateway\Infrastructure\Logger;
use TelegramGateway\Storage\Dto\StorableFile;
use TelegramGateway\Storage\Exception\StorageException;
use TelegramGateway\Storage\Exception\TelegramApiException;
use TelegramGateway\Storage\StorageInterface;
use Throwable;

/**
 * The single upload workflow shared by the Web UI and the REST API:
 * validate → extract metadata → store → normalize → log. Both Presentation
 * controllers call process() and nothing else — this is the only place the
 * workflow is implemented.
 */
final class UploadPipeline
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly MetadataService $metadataService,
        private readonly StorageInterface $storage,
        private readonly ResponseNormalizer $normalizer,
        private readonly Configuration $configuration,
        private readonly Logger $logger,
    ) {
    }

    public function process(IncomingFile $file, string $requestId): UploadResult
    {
        try {
            $this->validationService->validate($file);
            $metadata = $this->metadataService->extract($file);

            $storable = new StorableFile(
                filePath: $file->tmpPath,
                filename: $metadata->filename,
                mimeType: $metadata->mimeType,
                uploadMethod: $metadata->uploadMethod,
                fileSize: $metadata->size,
            );

            $storageResponse = $this->storage->store($storable);
            $normalized = $this->normalizer->normalize($storageResponse, $metadata, $this->configuration->telegramChatId());

            $this->logger->upload('Upload succeeded', [
                'request_id' => $requestId,
                'filename' => $metadata->filename,
                'method' => $metadata->uploadMethod->telegramApiMethod(),
                'file_id' => $normalized->fileId,
                'size' => $metadata->size,
            ]);

            return UploadResult::success($normalized);
        } catch (ValidationException $e) {
            $this->logger->warning('Upload rejected by validation', [
                'request_id' => $requestId,
                'error_code' => $e->errorCode(),
                'reason' => $e->getMessage(),
            ]);

            return UploadResult::failure($e->errorCode(), $e->getMessage());
        } catch (TelegramApiException $e) {
            $this->logger->telegram('Telegram API rejected upload', [
                'request_id' => $requestId,
                'telegram_error_code' => $e->telegramErrorCode(),
                'description' => $e->getMessage(),
            ]);

            return UploadResult::failure('telegram_api_error', 'Telegram rejected the upload: ' . $e->getMessage());
        } catch (StorageException $e) {
            $this->logger->error('Storage transport failure', [
                'request_id' => $requestId,
                'reason' => $e->getMessage(),
            ]);

            return UploadResult::failure('storage_error', 'Could not reach Telegram. Please try again.');
        } catch (Throwable $e) {
            $this->logger->error('Unexpected upload failure', [
                'request_id' => $requestId,
                'exception' => $e::class,
                'reason' => $e->getMessage(),
            ]);

            return UploadResult::failure('internal_error', 'An unexpected error occurred while uploading the file.');
        } finally {
            if (is_uploaded_file($file->tmpPath)) {
                @unlink($file->tmpPath);
            }
        }
    }
}
