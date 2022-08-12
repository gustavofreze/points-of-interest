<?php

namespace PointsOfInterest\Starter;

use PointsOfInterest\Driver\Http\PointOfInterest\Register\Register;
use PointsOfInterest\Driver\Http\PointOfInterest\Search\Search;
use Slim\App;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class Routes
{
    public function __construct(private readonly App $app)
    {
        $routeCollector = $this->app->getRouteCollector();
        $routeCollector->setDefaultInvocationStrategy(new RequestResponseArgs());
    }

    public function build(): void
    {
        $this->app->addErrorMiddleware(true, true, true);
        $this->app->addBodyParsingMiddleware();

        $this->app->group('/pois', function (RouteCollectorProxyInterface $group) {
            $group->get('', Search::class);
            $group->post('', Register::class);
        });
    }
}
