<?php

declare(strict_types=1);

require __DIR__ . '/src/Infrastructure/Autoloader.php';

use TelegramGateway\Infrastructure\Autoloader;
use TelegramGateway\Infrastructure\Bootstrap;
use TelegramGateway\Presentation\ErrorHandler;
use TelegramGateway\Presentation\Http\Request;
use TelegramGateway\Presentation\WebController;

Autoloader::register(__DIR__ . '/src');

$boot = Bootstrap::init(__DIR__ . '/config.php', __DIR__ . '/logs');

ErrorHandler::registerForHtml($boot->logger, $boot->configuration);

$controller = new WebController($boot->configuration);
$controller->handle(Request::fromGlobals());
