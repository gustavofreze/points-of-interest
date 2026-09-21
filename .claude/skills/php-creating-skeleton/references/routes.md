# Routes

Use this shape for the `src/Routes.php` class, a declarative bootstrap whose constructor sets the global middleware
chain and whose `register()` groups endpoints by context under versioned prefixes.

All three per-group middlewares below are placeholders, filled with the concrete classes the service wires in its
container. What holds on every service is the placement, authentication is a PSR-15 middleware attached to the route
group and never inline in a handler. `<Authentication>Middleware` is the class the `<auth-package>` package publishes,
and with `<auth-package>` unset the group ships without that line, while any authentication the service does add is
still a PSR-15 middleware on the group. `<Idempotency>` is the class the `<idempotency-package>` package publishes, and
with `<idempotency-package>` unset that line is dropped too and a write endpoint accepting `Idempotency-Key` implements
the guarantee itself. `<Scope>Middleware` is the tenancy gate, the class the `<idempotency-package>` package publishes
alongside the idempotency middleware, and it is present only in a multi-tenant service while that package binding holds.
With `<idempotency-package>` unset the service supplies the equivalent gate itself, still as a PSR-15 middleware on the
group. In a single-tenant service there is no tenant to isolate, and the line is dropped rather than failed.

```php
<?php

declare(strict_types=1);

// src/Routes.php. Declarative bootstrap under the infrastructure files exception. The constructor sets
// the invocation strategy and adds the global middleware chain outermost first. register() groups
// endpoints by context under versioned prefixes and attaches per-group middleware pulled from the
// container. The invariants live in php-architecture (section Infrastructure files exception).

final readonly class Routes
{
    public function __construct(private App $app)
    {
        $container = $this->app->getContainer();

        $strategy = new RequestResponseArgs();
        $this->app->getRouteCollector()->setDefaultInvocationStrategy($strategy);

        $this->app->add($container->get(ErrorMiddleware::class));
        $this->app->add($container->get(LogMiddleware::class));
        $this->app->addBodyParsingMiddleware();
    }

    public function register(): void
    {
        $this->app->get('/health/liveness', LivenessHandler::class);
        $this->app->get('/health/readiness', ReadinessHandler::class);

        $container = $this->app->getContainer();

        $this->app->group('/v1', function (RouteCollectorProxyInterface $route): void {
            $route->group('/<collection>', function (RouteCollectorProxyInterface $group): void {
                $group->get('', Find<Collection>::class);
                $group->post('', Create<Resource>::class);
                $group->get('/{<resource>_id}', Find<Resource>ById::class);
                $group->post('/{<resource>_id}/<operations>', <Operation><Resource>::class);
            });
        })
            ->addMiddleware($container->get(<Idempotency>::class))
            ->addMiddleware($container->get(<Scope>Middleware::class))
            ->addMiddleware($container->get(<Authentication>Middleware::class));
    }
}
```
