<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

/**
 * Generic cURL-based HTTP client. This class knows nothing about Telegram
 * or any other provider — it only performs HTTP requests and reports what
 * happened. Business meaning is attached by the caller (the Storage layer).
 */
final class HttpClient
{
    public function __construct(
        private readonly int $timeoutSeconds = 30,
        private readonly int $connectTimeoutSeconds = 10,
        private readonly ?string $proxy = null,
    ) {
    }

    public function get(string $url): HttpResponse
    {
        return $this->execute($url, [CURLOPT_HTTPGET => true]);
    }

    /**
     * @param array<string, mixed> $fields Scalars are sent as form fields;
     *   CURLFile instances trigger multipart/form-data encoding.
     */
    public function postMultipart(string $url, array $fields): HttpResponse
    {
        return $this->execute($url, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => ['Expect:'],
        ]);
    }

    /**
     * @param array<int, mixed> $extraOptions
     */
    private function execute(string $url, array $extraOptions): HttpResponse
    {
        $handle = curl_init($url);
        if ($handle === false) {
            return new HttpResponse(0, '', 'Failed to initialize cURL handle.');
        }

        $options = $extraOptions + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ];

        curl_setopt_array($handle, $options);

        if ($this->proxy !== null) {
            curl_setopt($handle, CURLOPT_PROXY, $this->proxy);
        }

        $body = curl_exec($handle);

        if ($body === false) {
            $error = curl_error($handle);
            $errno = curl_errno($handle);
            curl_close($handle);

            return new HttpResponse(0, '', "cURL error {$errno}: {$error}");
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return new HttpResponse($statusCode, (string) $body, null);
    }
}
