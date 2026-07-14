<?php

declare(strict_types=1);

namespace TelegramGateway\Presentation;

use TelegramGateway\Application\UploadPipeline;
use TelegramGateway\Infrastructure\Logger;
use TelegramGateway\Infrastructure\Uuid;
use TelegramGateway\Presentation\Http\Request;
use TelegramGateway\Presentation\Http\Response;

/**
 * REST API entry point: POST multipart/form-data with a "file" field,
 * always returns JSON. Contains no business logic itself — it only reads
 * the request, delegates to UploadPipeline, and renders the result.
 */
final class ApiController
{
    public function __construct(
        private readonly UploadPipeline $pipeline,
        private readonly Response $response,
        private readonly Logger $logger,
    ) {
    }

    public function handle(Request $request): void
    {
        $requestId = Uuid::v4();

        if ($request->method() !== 'POST') {
            $this->response->error($requestId, 'method_not_allowed', 'Only POST is supported.', 405);
        }

        if (!$request->isMultipart()) {
            $this->response->error($requestId, 'invalid_content_type', 'Content-Type must be multipart/form-data.', 415);
        }

        $file = $request->uploadedFile('file');
        if ($file === null) {
            $this->response->error($requestId, 'missing_file', 'No file was provided in the "file" field.', 400);
        }

        $this->logger->api('Upload request received', ['request_id' => $requestId, 'filename' => $file->originalName]);

        $result = $this->pipeline->process($file, $requestId);

        if ($result->isSuccess() && $result->data !== null) {
            $this->response->success($requestId, $result->data->toArray());
        }

        $this->response->error(
            $requestId,
            $result->errorCode ?? 'internal_error',
            $result->errorMessage ?? 'An unexpected error occurred.',
            $this->httpStatusForErrorCode($result->errorCode ?? 'internal_error')
        );
    }

    private function httpStatusForErrorCode(string $errorCode): int
    {
        return match ($errorCode) {
            'missing_file', 'invalid_content_type', 'upload_error', 'invalid_upload', 'empty_file',
            'file_too_large', 'invalid_filename', 'extension_blocked', 'extension_not_allowed',
            'mime_type_blocked', 'mime_type_not_allowed' => 400,
            'telegram_api_error', 'storage_error' => 502,
            default => 500,
        };
    }
}
