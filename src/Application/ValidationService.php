<?php

declare(strict_types=1);

namespace TelegramGateway\Application;

use TelegramGateway\Application\Dto\IncomingFile;
use TelegramGateway\Application\Exception\ValidationException;
use TelegramGateway\Infrastructure\Configuration;
use TelegramGateway\Infrastructure\FileInfo;

/**
 * Centralized validation for every uploaded file, regardless of whether it
 * arrived through the Web UI or the REST API. Both entry points call this
 * same service so validation rules can never drift between them.
 */
final class ValidationService
{
    public function __construct(private readonly Configuration $configuration)
    {
    }

    /**
     * @throws ValidationException
     */
    public function validate(IncomingFile $file): void
    {
        $this->assertUploadSucceeded($file);
        $this->assertIsGenuineUpload($file);
        $this->assertNotEmpty($file);
        $this->assertWithinSizeLimit($file);
        $this->assertFilenameIsSafe($file);
        $this->assertExtensionAllowed(FileInfo::extensionFromFilename($file->originalName));
        $this->assertMimeTypeAllowed(FileInfo::detectMimeType($file->tmpPath));
    }

    private function assertUploadSucceeded(IncomingFile $file): void
    {
        if ($file->uploadError === UPLOAD_ERR_OK) {
            return;
        }

        throw new ValidationException('upload_error', $this->describeUploadError($file->uploadError));
    }

    private function describeUploadError(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file exceeds the maximum upload size allowed by the server.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary directory configured for uploads.',
            UPLOAD_ERR_CANT_WRITE => 'The server failed to write the uploaded file to disk.',
            UPLOAD_ERR_EXTENSION => 'A server extension stopped the file upload.',
            default => 'The file upload failed for an unknown reason.',
        };
    }

    /**
     * Defends against a request that supplies a filesystem path that was
     * never actually produced by PHP's own upload mechanism.
     */
    private function assertIsGenuineUpload(IncomingFile $file): void
    {
        if (is_uploaded_file($file->tmpPath)) {
            return;
        }

        throw new ValidationException('invalid_upload', 'The uploaded file could not be verified.');
    }

    private function assertNotEmpty(IncomingFile $file): void
    {
        if ($file->size > 0) {
            return;
        }

        throw new ValidationException('empty_file', 'The uploaded file is empty.');
    }

    private function assertWithinSizeLimit(IncomingFile $file): void
    {
        $maxSize = $this->configuration->maxUploadSizeBytes();

        if ($file->size <= $maxSize) {
            return;
        }

        $limit = FileInfo::humanFileSize($maxSize);
        throw new ValidationException('file_too_large', "The file exceeds the maximum allowed size of {$limit}.");
    }

    private function assertFilenameIsSafe(IncomingFile $file): void
    {
        $name = $file->originalName;

        if ($name === '' || strlen($name) > 255 || preg_match('/[\x00-\x1F\x7F]/', $name) === 1) {
            throw new ValidationException('invalid_filename', 'The uploaded file has an invalid filename.');
        }
    }

    private function assertExtensionAllowed(string $extension): void
    {
        $blocked = $this->configuration->blockedExtensions();
        if ($extension !== '' && in_array($extension, $blocked, true)) {
            throw new ValidationException('extension_blocked', "Files with the .{$extension} extension are not allowed.");
        }

        $allowed = $this->configuration->allowedExtensions();
        if ($allowed !== [] && !in_array($extension, $allowed, true)) {
            throw new ValidationException('extension_not_allowed', "Files with the .{$extension} extension are not supported.");
        }
    }

    private function assertMimeTypeAllowed(string $mimeType): void
    {
        $blocked = $this->configuration->blockedMimeTypes();
        if (in_array($mimeType, $blocked, true)) {
            throw new ValidationException('mime_type_blocked', "Files of type {$mimeType} are not allowed.");
        }

        $allowed = $this->configuration->allowedMimeTypes();
        if ($allowed !== [] && !in_array($mimeType, $allowed, true)) {
            throw new ValidationException('mime_type_not_allowed', "Files of type {$mimeType} are not supported.");
        }
    }
}
