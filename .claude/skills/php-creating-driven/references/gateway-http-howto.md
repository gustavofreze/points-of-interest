# Gateway HTTP how-to (body-on-intent)

Read this while scaffolding a gateway. None of it is a per-file invariant, so it is NOT path-injected. The invariants
live in `php-hex-driven-http`. This file holds the decision support and the transport concerns that live in decorators,
not in the adapter body.

## Vendor SDK path

When a provider offers an official PHP SDK (`stripe/stripe-php`, `aws/aws-sdk-php`, `mongodb/mongodb`), the adapter MAY
depend on the SDK's typed client instead of `TinyBlocks\Http\Http`. The gateway obligations still apply.

- Non-success SDK errors translate to canonical application exceptions before crossing the `Driven/` boundary.
- Timeouts, retry, and circuit-breaker behavior come from the SDK's typed configuration object, populated from the
  Settings VO.
- Idempotency on POST-equivalent operations follows the SDK's idempotency-key parameter.

Never mix the `Http` facade and a vendor SDK in one gateway class. One transport per gateway. A vendor-SDK gateway uses
the SDK's own test seam, not `InMemoryTransport`.

## Timeouts

1. Every request runs under explicit connect and read timeouts set on the PSR-18 client that `NetworkTransport` wraps.
   Never rely on PSR-18 client defaults (Guzzle's default of 0 means wait forever).
2. Timeout values come from the Settings VO and are applied at the `Dependencies.php` binding that builds the production
   transport. Always set a total timeout to prevent cascading delays.

## Retry policy

1. Retryable status codes: `429`, `500`, `502`, `503`, `504`. Non-retryable: `400`, `401`, `403`, `404`, `409`, `422`.
   The application exception thrown for a non-retryable code propagates to the handler immediately.
2. Strategy is exponential backoff with jitter. Backoff base, jitter range, max attempts, and total timeout come from
   the Settings VO.
3. Retry logic lives in a `Transport` decorator that wraps the inner `Transport` and is bound on the facade in
   `src/Dependencies.php`. The adapter contains no retry loops.
4. Retries of POST rely on the `Idempotency-Key` header. A POST without an idempotency key is NOT retried.

## Circuit breaker

1. Transitions: closed to open after consecutive failures, half-open after a cooldown, closed again after consecutive
   successes.
2. Thresholds, cooldown, and probe count come from the Settings VO.
3. When open, the decorator raises an `HttpException` that the adapter translates into the matching application
   exception (for example `PaymentChargingGatewayUnavailable`).
4. Like retry, circuit-breaker logic lives in a `Transport` decorator, not in the adapter body.

## Caching

The HTTP adapter is stateless and does NOT cache responses internally. When caching is required it lives in a decorator
over the port (for example `CachedPaymentChargingGateway implements PaymentCharging` delegating to the real gateway),
bound under the port name in `src/Dependencies.php`. When the API supports HTTP caching headers (`ETag`,
`Cache-Control`, `Last-Modified`), the adapter respects them by storing the `ETag` and sending `If-None-Match`. The ETag
storage lives in the cache decorator.

## Logging and observability

1. Log every outbound request at info level (method, URL with sensitive query parameters redacted, status, duration).
   Logging lives in a `Transport` decorator, not in the adapter body.
2. Never log request or response bodies carrying personal data, secrets, or payment data. The decorator masks these
   fields before emitting the line.
3. Trace headers (`traceparent`, `X-Request-Id`) are propagated by the decorator, not by each adapter.

## What the adapter does NOT do

1. Caching of responses (lives in a port decorator, the adapter is stateless).
2. Scheduling and batching (outside HTTP scope, one request per method call).
3. Translating between two providers' shapes (each provider gets its own adapter implementing the port, the DI container
   decides which one to bind).
4. Reading environment variables (the Settings VO does that).
5. Building the request body or parsing the response inline (payload objects under `Payload/Request/` and
   `Payload/Response/` do that).

## Shared versus per-gateway error enum

The `references/gateway-error-enum.md` enum is per-gateway by default. When several providers in one context share the
same failure taxonomy, a single shared `GatewayError.php` mapping to the concrete `<Operation>` exceptions is allowed,
colocated with the providers that share it. Keep the two-operation shape (`from(?Code)` then `toException()`) either
way.

## Status translator collaborator (optional)

The two-tier Gateway template shows a `<Provider><Resource>StatusTranslator` collaborator for providers that return
provider-specific status strings on a read path. It is optional. A provider whose read path returns a value object
directly, or that needs a second resource client instead (a `<Provider><Resource>` client composed beside the Gateway),
omits the translator. Inject what the provider actually needs.
