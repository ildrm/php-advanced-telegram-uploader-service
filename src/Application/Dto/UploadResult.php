<?php

declare(strict_types=1);

namespace TelegramGateway\Application\Dto;

/**
 * The outcome of running one file through the UploadPipeline: either a
 * successful NormalizedUploadData, or a safe error code/message pair
 * suitable for returning to a client.
 */
final readonly class UploadResult
{
    private function __construct(
        public bool $success,
        public ?NormalizedUploadData $data,
        public ?string $errorCode,
        public ?string $errorMessage,
    ) {
    }

    public static function success(NormalizedUploadData $data): self
    {
        return new self(true, $data, null, null);
    }

    public static function failure(string $errorCode, string $errorMessage): self
    {
        return new self(false, null, $errorCode, $errorMessage);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
