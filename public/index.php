<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PointsOfInterest\Dependencies;
use PointsOfInterest\Routes;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(Dependencies::definitions());

$app = Bridge::create($containerBuilder->build());

$routes = new Routes(app: $app);
$routes->register();

$app->run();
