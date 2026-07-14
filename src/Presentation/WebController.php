<?php

declare(strict_types=1);

namespace TelegramGateway\Presentation;

use TelegramGateway\Infrastructure\Configuration;
use TelegramGateway\Infrastructure\FileInfo;
use TelegramGateway\Presentation\Http\Request;

/**
 * Renders the Web UI. This controller never touches Telegram or the
 * upload workflow directly — the page's own JavaScript uploads files via
 * the REST API (api/index.php), so both entry points run the exact same
 * UploadPipeline through the exact same HTTP endpoint.
 */
final class WebController
{
    public function __construct(private readonly Configuration $configuration)
    {
    }

    public function handle(Request $request): void
    {
        if ($request->method() !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Method Not Allowed';

            return;
        }

        header('Content-Type: text/html; charset=utf-8');

        $viewData = [
            'maxFileSize' => $this->configuration->maxUploadSizeBytes(),
            'maxFileSizeHuman' => FileInfo::humanFileSize($this->configuration->maxUploadSizeBytes()),
            'allowedExtensions' => $this->configuration->allowedExtensions(),
            'appMode' => $this->configuration->appMode(),
        ];

        require __DIR__ . '/Templates/upload-page.php';
    }
}
