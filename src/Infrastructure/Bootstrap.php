<?php

declare(strict_types=1);

namespace TelegramGateway\Infrastructure;

use TelegramGateway\Infrastructure\Exception\ConfigurationException;

/**
 * Loads config.php, builds the Configuration/Logger/Clock services shared
 * by both entry points (index.php and api/index.php), and applies the
 * process-wide settings (timezone) that must be set once per request.
 *
 * This class intentionally does not build a full dependency-injection
 * container — the rest of the object graph (validation, storage, the
 * upload pipeline) is wired explicitly in each entry point, which keeps the
 * dependency flow visible instead of hidden behind a container.
 */
final class Bootstrap
{
    public static function init(string $configPath, string $logsDirectory): BootstrapResult
    {
        if (!is_file($configPath)) {
            throw new ConfigurationException('Configuration file config.php was not found.');
        }

        $configArray = require $configPath;

        if (!is_array($configArray)) {
            throw new ConfigurationException('config.php must return an array.');
        }

        if (!isset($configArray['logging']['directory']) || $configArray['logging']['directory'] === '') {
            $configArray['logging']['directory'] = $logsDirectory;
        }

        $configuration = new Configuration($configArray);

        date_default_timezone_set($configuration->timezone());

        $clock = new Clock();
        $logger = new Logger($configuration, $clock);
        $logger->maybeRunRetention();

        return new BootstrapResult($configuration, $logger, $clock);
    }
}
