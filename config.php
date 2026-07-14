<?php

/**
 * Telegram Storage Gateway — configuration.
 *
 * Every configurable value lives in this single file and is read through
 * TelegramGateway\Infrastructure\Configuration. Nothing else in the
 * application should hard-code a value that belongs here.
 *
 * Keep this file outside of version control (or with real credentials
 * removed) once you have filled in your bot token and chat id — the
 * bundled .htaccess already blocks direct web access to it.
 */

declare(strict_types=1);

return [

    'app' => [
        // 'production' hides all internal error detail from responses.
        // 'development' is more verbose — never use it on a public host.
        'mode' => 'production',

        // Only takes effect when mode is 'development'.
        'debug' => false,

        'timezone' => 'UTC',
    ],

    'telegram' => [
        // Create a bot with @BotFather on Telegram to obtain this token.
        'bot_token' => '',

        // The chat (user, group, or channel) the bot uploads files into.
        // The bot must already be a member of/have started a chat with
        // this id. Every upload goes to this single, server-configured
        // destination — clients can never choose an arbitrary chat.
        'chat_id' => '',

        'api_base_url' => 'https://api.telegram.org',

        // Seconds to wait for the full upload request to Telegram.
        'timeout' => 60,

        // Seconds to wait for the initial connection to Telegram.
        'connect_timeout' => 10,

        // Outbound proxy for the Telegram API, e.g. 'http://127.0.0.1:8080'.
        // Leave null when no proxy is required.
        'proxy' => null,
    ],

    'upload' => [
        // Telegram bots may upload files up to 50 MB via this direct
        // multipart method (see https://core.telegram.org/bots/api).
        // Lower this if your hosting's own PHP limits are smaller — see
        // README.md for the matching php.ini/.htaccess settings.
        'max_file_size' => 50 * 1024 * 1024,

        // Whitelist. Only these extensions may be uploaded. Leave empty
        // to allow any extension not present in blocked_extensions.
        'allowed_extensions' => [
            // Images
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'svg', 'bmp', 'tiff', 'ico',
            // Documents
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
            'json', 'xml', 'zip', 'rar', '7z', 'md', 'rtf',
            // Source code
            'php', 'js', 'css', 'html', 'htm', 'py', 'java', 'go', 'rs', 'c', 'cpp', 'h', 'hpp',
            // Audio
            'mp3', 'aac', 'wav', 'flac', 'ogg', 'oga', 'm4a', 'wma',
            // Video
            'mp4', 'mov', 'avi', 'mkv', 'webm', 'mpeg', 'mpg', 'm4v', '3gp',
        ],

        // Blacklist. Checked before allowed_extensions and always wins,
        // so operators can keep an allow-all policy (empty
        // allowed_extensions) while still blocking dangerous types.
        'blocked_extensions' => [
            'exe', 'dll', 'msi', 'bat', 'cmd', 'com', 'scr', 'vbs', 'ps1', 'jar', 'apk', 'msix',
        ],

        // Whitelist, matched against the file's real server-detected MIME
        // type (never the client-supplied one). Leave empty to allow any
        // MIME type not present in blocked_mime_types.
        'allowed_mime_types' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif',
            'image/svg+xml', 'image/bmp', 'image/tiff', 'image/x-icon',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/json', 'application/xml', 'text/xml', 'application/zip',
            'application/x-rar-compressed', 'application/x-7z-compressed',
            'application/rtf', 'text/markdown', 'text/csv',
            'audio/mpeg', 'audio/aac', 'audio/wav', 'audio/x-wav', 'audio/flac',
            'audio/ogg', 'audio/mp4', 'audio/x-ms-wma',
            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska',
            'video/webm', 'video/mpeg', 'video/3gpp',
            // Many source/text files are reported generically by the
            // server's fileinfo database; allow both umbrella types so
            // legitimate source code and plain-text uploads are not
            // rejected purely because of an imprecise MIME detection.
            'text/plain', 'application/octet-stream',
        ],

        // Blacklist, matched against the real server-detected MIME type.
        'blocked_mime_types' => [],
    ],

    'logging' => [
        'enabled' => true,

        // Left empty to default to the bundled logs/ directory.
        'directory' => __DIR__ . '/logs',

        'retention_days' => 14,
        'max_total_size_mb' => 100,

        // When true, logs older than retention_days are gzip-compressed
        // instead of deleted immediately, and only removed once older
        // than compressed_retention_days.
        'compress_old_logs' => true,
        'compressed_retention_days' => 60,
    ],

];
