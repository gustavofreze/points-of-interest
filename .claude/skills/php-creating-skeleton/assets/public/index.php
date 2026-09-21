<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use <RootNamespace>\Dependencies;
use <RootNamespace>\Routes;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(Dependencies::definitions());

$app = Bridge::create($containerBuilder->build());

$routes = new Routes(app: $app);
$routes->register();

$app->run();
