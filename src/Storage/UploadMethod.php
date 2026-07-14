<?php

declare(strict_types=1);

namespace TelegramGateway\Storage;

/**
 * The Telegram Bot API upload methods this gateway can dispatch to. Owned
 * by the Storage layer because mapping a category to a concrete API method
 * name and multipart field name is Telegram-specific knowledge; the
 * Application layer only decides *which* case applies to a given file.
 */
enum UploadMethod: string
{
    case Photo = 'photo';
    case Video = 'video';
    case Audio = 'audio';
    case Voice = 'voice';
    case Document = 'document';

    public function telegramApiMethod(): string
    {
        return match ($this) {
            self::Photo => 'sendPhoto',
            self::Video => 'sendVideo',
            self::Audio => 'sendAudio',
            self::Voice => 'sendVoice',
            self::Document => 'sendDocument',
        };
    }

    /** The multipart field name Telegram expects the file under for this method. */
    public function multipartFieldName(): string
    {
        return $this->value;
    }
}
