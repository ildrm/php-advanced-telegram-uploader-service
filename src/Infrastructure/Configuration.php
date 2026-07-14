<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

use TelegramGateway\Infrastructure\Exception\ConfigurationException;

/**
 * Typed, read-only access to the values returned by config.php.
 *
 * Every other component reads configuration through this class instead of
 * touching the config array directly, so the shape of config.php only needs
 * to be understood in one place.
 */
final class Configuration
{
    private readonly array $app;
    private readonly array $telegram;
    private readonly array $upload;
    private readonly array $logging;

    public function __construct(array $config)
    {
        foreach (['app', 'telegram', 'upload', 'logging'] as $section) {
            if (!isset($config[$section]) || !is_array($config[$section])) {
                throw new ConfigurationException("config.php is missing the required '{$section}' section.");
            }
        }

        $this->app = $config['app'];
        $this->telegram = $config['telegram'];
        $this->upload = $config['upload'];
        $this->logging = $config['logging'];
    }

    public function appMode(): string
    {
        $mode = (string) ($this->app['mode'] ?? 'production');

        return in_array($mode, ['production', 'development'], true) ? $mode : 'production';
    }

    public function isDebug(): bool
    {
        return $this->appMode() === 'development' && (bool) ($this->app['debug'] ?? false);
    }

    public function timezone(): string
    {
        $timezone = (string) ($this->app['timezone'] ?? 'UTC');

        return $timezone !== '' ? $timezone : 'UTC';
    }

    /**
     * Throws unless a bot token and chat id have been configured. Called
     * lazily, only by code paths that actually need to talk to Telegram, so
     * the web UI keeps working even before the bot has been configured.
     */
    public function assertTelegramIsConfigured(): void
    {
        if ($this->telegramBotToken() === '' || $this->telegramChatId() === '') {
            throw new ConfigurationException(
                'Telegram is not configured. Set telegram.bot_token and telegram.chat_id in config.php.'
            );
        }
    }

    public function telegramBotToken(): string
    {
        return trim((string) ($this->telegram['bot_token'] ?? ''));
    }

    public function telegramChatId(): string
    {
        return trim((string) ($this->telegram['chat_id'] ?? ''));
    }

    public function telegramApiBaseUrl(): string
    {
        $url = (string) ($this->telegram['api_base_url'] ?? 'https://api.telegram.org');

        return rtrim($url, '/');
    }

    public function telegramTimeoutSeconds(): int
    {
        return max(1, (int) ($this->telegram['timeout'] ?? 30));
    }

    public function telegramConnectTimeoutSeconds(): int
    {
        return max(1, (int) ($this->telegram['connect_timeout'] ?? 10));
    }

    public function telegramProxy(): ?string
    {
        $proxy = $this->telegram['proxy'] ?? null;

        return is_string($proxy) && $proxy !== '' ? $proxy : null;
    }

    public function maxUploadSizeBytes(): int
    {
        return max(1, (int) ($this->upload['max_file_size'] ?? 52428800));
    }

    /** @return list<string> lower-case extensions without a leading dot */
    public function allowedExtensions(): array
    {
        return $this->normalizeList($this->upload['allowed_extensions'] ?? []);
    }

    /** @return list<string> lower-case extensions without a leading dot */
    public function blockedExtensions(): array
    {
        return $this->normalizeList($this->upload['blocked_extensions'] ?? []);
    }

    /** @return list<string> lower-case MIME types */
    public function allowedMimeTypes(): array
    {
        return $this->normalizeList($this->upload['allowed_mime_types'] ?? []);
    }

    /** @return list<string> lower-case MIME types */
    public function blockedMimeTypes(): array
    {
        return $this->normalizeList($this->upload['blocked_mime_types'] ?? []);
    }

    public function logsDirectory(): string
    {
        return (string) ($this->logging['directory'] ?? '');
    }

    public function loggingEnabled(): bool
    {
        return (bool) ($this->logging['enabled'] ?? true);
    }

    public function logRetentionDays(): int
    {
        return max(1, (int) ($this->logging['retention_days'] ?? 14));
    }

    public function logMaxTotalSizeBytes(): int
    {
        $megabytes = max(1, (int) ($this->logging['max_total_size_mb'] ?? 100));

        return $megabytes * 1024 * 1024;
    }

    public function compressOldLogs(): bool
    {
        return (bool) ($this->logging['compress_old_logs'] ?? true);
    }

    public function compressedLogRetentionDays(): int
    {
        return max($this->logRetentionDays(), (int) ($this->logging['compressed_retention_days'] ?? 60));
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = array_map(
            static fn (mixed $item): string => strtolower(trim((string) $item)),
            $value
        );

        return array_values(array_filter($normalized, static fn (string $item): bool => $item !== ''));
    }
}
