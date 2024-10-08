<?php

declare(strict_types=1);

namespace PointsOfInterest\Starter;

use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\Register;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\RegisterExceptionHandler;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search\Search;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search\SearchExceptionHandler;
use PointsOfInterest\Driver\Http\Middlewares\ErrorHandling;
use Slim\App;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Interfaces\RouteCollectorProxyInterface;

final readonly class Routes
{
    public function __construct(private App $app)
    {
        $routeCollector = $this->app->getRouteCollector();
        $routeCollector->setDefaultInvocationStrategy(new RequestResponseArgs());
    }

    public function build(): void
    {
        $this->app->addErrorMiddleware(true, true, true);
        $this->app->addBodyParsingMiddleware();

        $this->app->group('/pois', function (RouteCollectorProxyInterface $group) {
            $group->get('', Search::class)->addMiddleware(
                new ErrorHandling(exceptionHandler: new SearchExceptionHandler())
            );
            $group->post('', Register::class)->addMiddleware(
                new ErrorHandling(exceptionHandler: new RegisterExceptionHandler())
            );
        });
    }
}
