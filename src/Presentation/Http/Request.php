<?php

declare(strict_types=1);

namespace TelegramGateway\Presentation\Http;

use TelegramGateway\Application\Dto\IncomingFile;

/**
 * Wraps the PHP superglobals for a single request so the rest of the
 * application never touches $_SERVER/$_FILES/$_POST directly.
 */
final class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $files
     */
    private function __construct(
        private readonly string $method,
        private readonly array $headers,
        private readonly array $files,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        return new self($method, self::collectHeaders(), $_FILES);
    }

    /** @return array<string, string> */
    private static function collectHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if ($headers !== false) {
                return array_change_key_case($headers, CASE_LOWER);
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_') && is_string($value)) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isMultipart(): bool
    {
        return str_starts_with($this->header('content-type') ?? '', 'multipart/form-data');
    }

    /**
     * Builds an IncomingFile DTO from a single-file upload field. Returns
     * null when the field is missing entirely (as opposed to present but
     * failed, which is surfaced through IncomingFile::$uploadError so
     * validation can report a precise reason).
     */
    public function uploadedFile(string $field): ?IncomingFile
    {
        $entry = $this->files[$field] ?? null;

        if (!is_array($entry) || !isset($entry['tmp_name'], $entry['name'], $entry['type'], $entry['size'], $entry['error'])) {
            return null;
        }

        if (is_array($entry['tmp_name'])) {
            // A multi-file field was sent; this API only supports one file
            // per request (clients loop for multiple files).
            return null;
        }

        return new IncomingFile(
            tmpPath: (string) $entry['tmp_name'],
            originalName: (string) $entry['name'],
            reportedMimeType: (string) $entry['type'],
            size: (int) $entry['size'],
            uploadError: (int) $entry['error'],
        );
    }
}
