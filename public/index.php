<?php

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use PointsOfInterest\Starter\Dependencies;
use PointsOfInterest\Starter\Routes;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Starter/Dependencies.php';
require_once __DIR__ . '/../src/Starter/Routes.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(Dependencies::definitions());

$app = Bridge::create($containerBuilder->build());

$routes = new Routes(app: $app);
$routes->build();

$app->run();
