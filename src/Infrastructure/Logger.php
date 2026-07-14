<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

/**
 * Lightweight, dependency-free file logger. Every event is written as one
 * JSON line to a single per-day log file so the whole day's activity can be
 * grepped or tailed without cross-referencing multiple files.
 *
 * Log retention (age- and size-based cleanup, with optional compression) is
 * run probabilistically on a small fraction of requests instead of via a
 * required cron job, so the application stays fully functional on hosts
 * where the operator never sets up scheduled tasks.
 */
final class Logger
{
    private const RETENTION_CHECK_PROBABILITY_PERCENT = 2;

    private readonly string $logsDirectory;
    private readonly bool $enabled;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly Clock $clock,
    ) {
        $this->logsDirectory = rtrim($configuration->logsDirectory(), '/\\');
        $this->enabled = $configuration->loggingEnabled() && $this->logsDirectory !== '';
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', 'general', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', 'general', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', 'general', $message, $context);
    }

    /** Logs suspicious or security-relevant events (blocked files, malformed requests). */
    public function security(string $message, array $context = []): void
    {
        $this->write('warning', 'security', $message, $context);
    }

    /** Logs Telegram Bot API failures and health information. */
    public function telegram(string $message, array $context = []): void
    {
        $this->write('info', 'telegram', $message, $context);
    }

    /** Logs REST API request handling. */
    public function api(string $message, array $context = []): void
    {
        $this->write('info', 'api', $message, $context);
    }

    /** Logs the outcome of an upload pipeline run. */
    public function upload(string $message, array $context = []): void
    {
        $this->write('info', 'upload', $message, $context);
    }

    private function write(string $level, string $channel, string $message, array $context): void
    {
        if (!$this->enabled) {
            return;
        }

        $line = [
            'timestamp' => $this->clock->now()->format(DATE_ATOM),
            'level' => $level,
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
        ];

        $encoded = json_encode($line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $encoded = json_encode(['timestamp' => $line['timestamp'], 'level' => $level, 'channel' => $channel, 'message' => $message]);
        }

        $this->appendLine((string) $encoded);
    }

    private function appendLine(string $line): void
    {
        if (!is_dir($this->logsDirectory)) {
            @mkdir($this->logsDirectory, 0755, true);
        }

        $path = $this->logsDirectory . DIRECTORY_SEPARATOR . 'app-' . $this->clock->now()->format('Y-m-d') . '.log';

        $written = @file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);

        if ($written === false) {
            // Logging itself failed; fall back to PHP's own error log rather
            // than losing the event or crashing the request.
            error_log('TelegramGateway logger write failed: ' . $line);
        }
    }

    /**
     * Runs log retention cleanup on a small, configurable percentage of
     * requests. Safe to call on every bootstrap — it is a cheap no-op most
     * of the time.
     */
    public function maybeRunRetention(): void
    {
        if (!$this->enabled || !is_dir($this->logsDirectory)) {
            return;
        }

        try {
            if (random_int(1, 100) > self::RETENTION_CHECK_PROBABILITY_PERCENT) {
                return;
            }
        } catch (\Random\RandomException) {
            return;
        }

        $this->runRetention();
    }

    private function runRetention(): void
    {
        $now = $this->clock->now()->getTimestamp();
        $retentionSeconds = $this->configuration->logRetentionDays() * 86400;
        $compressedRetentionSeconds = $this->configuration->compressedLogRetentionDays() * 86400;
        $compressOldLogs = $this->configuration->compressOldLogs();

        $entries = @scandir($this->logsDirectory);
        if ($entries === false) {
            return;
        }

        $files = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (!preg_match('/^app-\d{4}-\d{2}-\d{2}\.log(\.gz)?$/', $entry)) {
                continue;
            }

            $path = $this->logsDirectory . DIRECTORY_SEPARATOR . $entry;
            $mtime = @filemtime($path);
            if ($mtime === false) {
                continue;
            }

            $ageSeconds = $now - $mtime;
            $isCompressed = str_ends_with($entry, '.gz');

            if ($isCompressed) {
                if ($ageSeconds > $compressedRetentionSeconds) {
                    @unlink($path);
                    continue;
                }
            } elseif ($ageSeconds > $retentionSeconds) {
                if ($compressOldLogs && function_exists('gzencode')) {
                    $this->compressFile($path);
                } else {
                    @unlink($path);
                    continue;
                }
                $path .= '.gz';
            }

            if (is_file($path)) {
                $files[$path] = @filesize($path) ?: 0;
            }
        }

        $this->enforceMaxTotalSize($files);
    }

    private function compressFile(string $path): void
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            return;
        }

        $compressed = gzencode($data, 9);
        if ($compressed === false) {
            return;
        }

        if (@file_put_contents($path . '.gz', $compressed) !== false) {
            @unlink($path);
        }
    }

    /**
     * @param array<string, int> $filesBySize path => size in bytes
     */
    private function enforceMaxTotalSize(array $filesBySize): void
    {
        $totalSize = array_sum($filesBySize);
        $limit = $this->configuration->logMaxTotalSizeBytes();

        if ($totalSize <= $limit || $filesBySize === []) {
            return;
        }

        // Delete oldest files first until back under the configured limit.
        asort($filesBySize);
        uksort($filesBySize, static function (string $a, string $b): int {
            return (@filemtime($a) ?: 0) <=> (@filemtime($b) ?: 0);
        });

        foreach ($filesBySize as $path => $size) {
            if ($totalSize <= $limit) {
                break;
            }

            if (@unlink($path)) {
                $totalSize -= $size;
            }
        }
    }
}
