---
description: HTTP rules for the outbound side. Every *Gateway.php under src/Driven/ that calls an external API, whatever the service named the concern folder. The structural residue only, the Http facade dependency, the https transport guard, the single isSuccess guard delegating to the error enum, and the gateway-failure exception layering. The canonical shapes and the transport-decorator how-to live in the php-creating-driven skill.
paths:
    - "src/Driven/**/*Gateway.php"
---

# HTTP (outbound)

Invariants for every `*Gateway.php` under `src/Driven/` that consumes an external HTTP API. The concern folder grouping
an aggregate's providers is the service's own term and never a fixed folder name (`php-hex-driven` § Adapter kinds), so
this rule binds on the `Gateway` filename and not on any path segment below `src/Driven/`. That same table reserves the
`Gateway` suffix for the external HTTP adapter kind, so a file under `src/Driven/` carrying the suffix while performing
no outbound HTTP is misnamed, and the fix is the filename, never an `Http` facade dependency the class has no use for.
One carve-out to that reading: a `Gateway` whose whole job is choosing among other gateways (a selector or a registry
that only routes) performs no outbound HTTP by design, so it is out of scope for the checklist and every section below,
and the prohibition still stands that it never acquires an `Http` facade dependency it has no use for. The inbound side
(endpoints, exception-to-status mapping, response shape) is owned by `php-hex-driver-http`. The `<Provider>ApiSettings`
value object follows the base Settings contract owned by `php-architecture`.

This rule keeps only what must hold on every `*Gateway.php` file, wherever the service placed it. The canonical shapes
(Client, Gateway, payloads, the error enum, the production binding) and the transport-decorator how-to (timeout, retry,
circuit breaker, caching, logging, vendor SDK) are in the php-creating-driven skill.

## Pre-output checklist

1. The adapter depends on `TinyBlocks\Http\Http`, the facade. Never on a concrete HTTP client and never on
   `Psr\Http\Client\ClientInterface`. See § HTTP transport dependency.
2. The base URL uses `https://`, guarded by the concrete `<Provider>Client` (or the Gateway when no Client exists),
   never inside `<Provider>ApiSettings` (Settings never throw, `php-architecture` § Settings) and never at the
   composition root (declarative wiring only). TLS verification stays on. See § Transport security.
3. A non-2xx response translates to a canonical application exception through a single `!isSuccess()` guard that
   delegates to a `<Gateway>Error` enum, never an inline per-status if-ladder. See § Status to exception translation.
4. Gateway and port failures live in `Application/Exceptions/`. Purely technical infrastructure failures stay
   `RuntimeException`. Neither is a domain exception. See § Gateway failure exception layering.

## HTTP transport dependency

The adapter depends on `TinyBlocks\Http\Http`, the facade. It builds requests as `TinyBlocks\Http\Client\Request` and
reads `TinyBlocks\Http\Client\Response`. It NEVER depends on `Psr\Http\Client\ClientInterface`, `GuzzleHttp\Client`,
`GuzzleHttp\ClientInterface`, or any other concrete HTTP library, and it NEVER builds PSR-7 messages by hand. The
variable part is the `TinyBlocks\Http\Client\Transport` the facade is built over (production over `NetworkTransport`,
tests over `InMemoryTransport`), so the adapter is identical either way. The transport seam, the decorator binding, and
the vendor-SDK path are in the php-creating-driven skill.

## Transport security

1. The configured base URL MUST use the `https://` scheme. TLS certificate verification stays enabled in the PSR-18
   client wrapped by `NetworkTransport`. Disabling `verify` in production is prohibited.
2. The concrete `<Provider>Client` constructor (or the Gateway when no Client exists) guards this invariant: it throws a
   provider `InsecureBaseUrl` naming the URL when the base URL does not start with `https://`. A single carve-out allows
   a non-`https://` base URL when the Settings value `allowsInsecureBaseUrl` (read from `APP_ENV`, `local` only) is
   true. Production never allows plain HTTP. The guard never lives in `<Provider>ApiSettings` (Settings carry
   configuration values and never throw, `php-architecture` § Settings) and never at the composition root (declarative
   wiring only, and coverage-excluded code cannot be tested or mutation-killed).
3. Custom CA bundles are mounted at a fixed path and referenced via environment variable, never embedded in the source
   tree.

## Status to exception translation

A non-2xx response is a well-formed response, so the `Transport` returns it rather than throwing. The adapter splits
success from failure with a single `Response::isSuccess()` guard. The not-success branch passes `Response::code()` to a
`<Gateway>Error` enum and throws the result, never an inline per-status if-ladder. A transport-level failure (DNS,
connection refused, timeout) has no status, so the catch passes `null` and the enum resolves it to the timeout case.
`TinyBlocks\Http\Exceptions\HttpException` is caught and re-thrown the same way. The literal `<Gateway>Error` enum
shape, with its `from(?Code)`and `toException()` operations, is in the php-creating-driven skill.

## Gateway failure exception layering

A gateway or port failure is NOT a domain exception. Port outcomes (the gateway being unavailable, timing out, or
rejecting credentials) and business rejections (the provider declined a well-formed 4xx request) are application-layer
port failures in `src/Application/Exceptions/`, mapped to HTTP status by `Driver/Http/ExceptionMapping` at the inbound
boundary. Purely technical infrastructure failures (a raw transport `HttpException` before translation, broker errors,
file system errors) stay out of both the domain and the application layers and extend `RuntimeException`. This split is
delegated here by `php-hex-driver-http` (§ Exception to HTTP mapping). The `<Operation><Failure>` naming and the
canonical gateway exception list are in the php-creating-driven skill.

## Request building

The shapes and how-to are in the php-creating-driven skill.

## Provider request and response payloads

The shapes and how-to are in the php-creating-driven skill.

## Response handling

The shapes and how-to are in the php-creating-driven skill.

## Idempotency on outbound POST

The default: an outbound POST that creates a resource or moves money at a provider carries an idempotency key derived
from a value this service already owns and that is stable across retries (the aggregate identifier, or the outbox row
identifier for a fact being delivered), never a value regenerated per attempt, so a retry reaches the provider as the
same request. The header name and the key format per provider are owned by the spec at `<spec-root>`, read it via the
php-reading-spec skill, and the spec overrides this default where it speaks.

## Authentication and credentials

The shapes and how-to are in the php-creating-driven skill.
