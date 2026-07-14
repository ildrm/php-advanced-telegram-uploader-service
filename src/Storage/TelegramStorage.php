<?php

declare(strict_types=1);

namespace TelegramGateway\Storage;

use CURLFile;
use JsonException;
use TelegramGateway\Infrastructure\Configuration;
use TelegramGateway\Infrastructure\HttpClient;
use TelegramGateway\Infrastructure\Json;
use TelegramGateway\Storage\Dto\StorableFile;
use TelegramGateway\Storage\Dto\StorageResponse;
use TelegramGateway\Storage\Exception\StorageException;
use TelegramGateway\Storage\Exception\TelegramApiException;

/**
 * The only class in the application allowed to talk to the Telegram Bot
 * API. It sends the raw multipart request and returns Telegram's raw
 * response; converting that response into the application's normalized
 * internal representation is the Application layer's ResponseNormalizer.
 */
final class TelegramStorage implements StorageInterface
{
    private readonly string $botToken;
    private readonly string $chatId;
    private readonly string $apiBaseUrl;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly HttpClient $httpClient,
    ) {
        $this->botToken = $configuration->telegramBotToken();
        $this->chatId = $configuration->telegramChatId();
        $this->apiBaseUrl = $configuration->telegramApiBaseUrl();
    }

    public function store(StorableFile $file): StorageResponse
    {
        $result = $this->call($file->uploadMethod->telegramApiMethod(), [
            'chat_id' => $this->chatId,
            $file->uploadMethod->multipartFieldName() => new CURLFile($file->filePath, $file->mimeType, $file->filename),
        ]);

        return new StorageResponse($file->uploadMethod, $result);
    }

    public function delete(int $messageId): bool
    {
        $this->call('deleteMessage', [
            'chat_id' => $this->chatId,
            'message_id' => (string) $messageId,
        ]);

        return true;
    }

    public function getMetadata(string $fileId): array
    {
        return $this->call('getFile', ['file_id' => $fileId]);
    }

    public function healthCheck(): bool
    {
        try {
            $this->call('getMe', []);

            return true;
        } catch (StorageException | TelegramApiException) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed> the Telegram "result" payload
     */
    private function call(string $method, array $fields): array
    {
        // Deferred until the moment Telegram is actually contacted (rather
        // than checked eagerly in the constructor) so that requests which
        // would be rejected for an unrelated reason — wrong HTTP method,
        // missing file field — still get their own specific error instead
        // of a generic configuration failure.
        $this->configuration->assertTelegramIsConfigured();

        $url = sprintf('%s/bot%s/%s', $this->apiBaseUrl, $this->botToken, $method);

        $response = $this->httpClient->postMultipart($url, $fields);

        if ($response->transportError !== null) {
            throw new StorageException("Could not reach Telegram: {$response->transportError}");
        }

        try {
            $decoded = Json::decode($response->body);
        } catch (JsonException) {
            throw new StorageException('Telegram returned a response that was not valid JSON.');
        }

        if (($decoded['ok'] ?? false) !== true) {
            throw TelegramApiException::fromApiError(
                (int) ($decoded['error_code'] ?? $response->statusCode),
                (string) ($decoded['description'] ?? 'Telegram rejected the request.')
            );
        }

        $result = $decoded['result'] ?? null;

        return is_array($result) ? $result : [];
    }
}
