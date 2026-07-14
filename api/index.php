<?php

declare(strict_types=1);

require __DIR__ . '/../src/Infrastructure/Autoloader.php';

use TelegramGateway\Application\MetadataService;
use TelegramGateway\Application\ResponseNormalizer;
use TelegramGateway\Application\UploadPipeline;
use TelegramGateway\Application\ValidationService;
use TelegramGateway\Infrastructure\Autoloader;
use TelegramGateway\Infrastructure\Bootstrap;
use TelegramGateway\Infrastructure\HttpClient;
use TelegramGateway\Presentation\ApiController;
use TelegramGateway\Presentation\ErrorHandler;
use TelegramGateway\Presentation\Http\Request;
use TelegramGateway\Presentation\Http\Response;
use TelegramGateway\Storage\TelegramStorage;

Autoloader::register(__DIR__ . '/../src');

$boot = Bootstrap::init(__DIR__ . '/../config.php', __DIR__ . '/../logs');
$response = new Response();

ErrorHandler::registerForJson($boot->logger, $boot->configuration, $response);

$httpClient = new HttpClient(
    timeoutSeconds: $boot->configuration->telegramTimeoutSeconds(),
    connectTimeoutSeconds: $boot->configuration->telegramConnectTimeoutSeconds(),
    proxy: $boot->configuration->telegramProxy(),
);

$pipeline = new UploadPipeline(
    validationService: new ValidationService($boot->configuration),
    metadataService: new MetadataService(),
    storage: new TelegramStorage($boot->configuration, $httpClient),
    normalizer: new ResponseNormalizer($boot->clock),
    configuration: $boot->configuration,
    logger: $boot->logger,
);

$controller = new ApiController($pipeline, $response, $boot->logger);
$controller->handle(Request::fromGlobals());
