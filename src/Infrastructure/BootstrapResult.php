<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

/**
 * The small set of shared services every entry point needs after bootstrap.
 */
final readonly class BootstrapResult
{
    public function __construct(
        public Configuration $configuration,
        public Logger $logger,
        public Clock $clock,
    ) {
    }
}
