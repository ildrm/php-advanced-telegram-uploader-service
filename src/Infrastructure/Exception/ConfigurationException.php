<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure\Exception;

use RuntimeException;

/**
 * Thrown when config.php is missing, malformed, or missing required values
 * (such as the Telegram bot token) needed to serve a request.
 */
final class ConfigurationException extends RuntimeException
{
}
