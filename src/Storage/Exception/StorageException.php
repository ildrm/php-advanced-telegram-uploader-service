<?php

declare(strict_types=1);

namespace TelegramGateway\Storage\Exception;

use RuntimeException;

/**
 * Thrown when a storage driver cannot complete a request for transport
 * reasons (network failure, timeout, malformed response) rather than
 * because the provider explicitly rejected it.
 */
final class StorageException extends RuntimeException
{
}
