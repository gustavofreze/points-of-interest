<?php

declare(strict_types=1);

namespace PointsOfInterest;

use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\RegisterPointOfInterest;
use PointsOfInterest\Query\PointOfInterest\FindAll\Http\FindPointsOfInterest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Interfaces\RouteCollectorProxyInterface;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\ErrorHandler\ErrorMiddleware;
use TinyBlocks\Http\Logging\LogMiddleware;
use TinyBlocks\HttpHealthCheck\LivenessHandler;
use TinyBlocks\HttpHealthCheck\ReadinessHandler;

final readonly class Routes
{
    public function __construct(private App $app)
    {
        $container = $this->app->getContainer();

        $this->app->getRouteCollector()->setDefaultInvocationStrategy(new RequestResponseArgs());

        $this->app->add($container->get(ErrorMiddleware::class));
        $this->app->add($container->get(LogMiddleware::class));
        $this->app->addBodyParsingMiddleware();
    }

    public function register(): void
    {
        $container = $this->app->getContainer();

        /** @var AppSettings $appSettings */
        $appSettings = $container->get(AppSettings::class);

        $this->app->get('/health/liveness', LivenessHandler::class);
        $this->app->get('/health/readiness', ReadinessHandler::class);

        $this->app->any(
            '/',
            fn(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface => $response
                ->withHeader('Location', $appSettings->source)
                ->withStatus(Code::FOUND->value)
        );

        $this->app->group('/points-of-interest', function (RouteCollectorProxyInterface $pointsOfInterest): void {
            $pointsOfInterest->get('', FindPointsOfInterest::class);
            $pointsOfInterest->post('', RegisterPointOfInterest::class);
        });
    }
}
