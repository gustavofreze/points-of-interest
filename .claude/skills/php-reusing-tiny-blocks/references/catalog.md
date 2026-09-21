# Tiny-blocks catalog

This catalog lists the `tiny-blocks/*` packages available on Packagist with their latest stable version. Do not edit the
table below by hand. Availability here does not mean a package is installed, check `composer.json` before assuming it is
present. The in-house `<vendor>/*` packages are private and maintained by hand below.

<!-- tiny-blocks:auto -->
<!-- Generated from https://packagist.org/packages/tiny-blocks/, do not edit by hand. -->

| Package                            | Latest version | Description                                                                                                                                                   |
|------------------------------------|----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tiny-blocks/building-blocks`      | 5.3.0          | Implements tactical DDD building blocks for PHP: entities, aggregate roots, domain events, snapshots, and upcasters.                                          |
| `tiny-blocks/collection`           | 2.6.0          | Models a type-safe, fluent collection API for PHP with eager and lazy pipelines over arrays, iterators, and generators.                                       |
| `tiny-blocks/country`              | 5.1.0          | Provides ISO 3166-1 country and ISO 3166-2 subdivision value objects for PHP, with Alpha-2, Alpha-3, numeric, and IANA timezone resolution.                   |
| `tiny-blocks/currency`             | 2.3.3          | Models ISO-4217 currencies as a PHP enum, with per-currency fraction digit resolution.                                                                        |
| `tiny-blocks/docker-container`     | 3.1.0          | Manages Docker containers programmatically for PHP, aimed at integration tests and disposable infrastructure.                                                 |
| `tiny-blocks/encoder`              | 4.0.2          | Encoder and decoder for arbitrary data.                                                                                                                       |
| `tiny-blocks/environment-variable` | 2.0.0          | Provides a type-safe environment variable reader for PHP, with strict integer and boolean conversion.                                                         |
| `tiny-blocks/http`                 | 7.2.0          | Implements PSR-7, PSR-15, PSR-17 and PSR-18 HTTP primitives for PHP, with a fluent response builder, cookies, cache control, and a PSR-18 client facade.      |
| `tiny-blocks/http-correlation-id`  | 2.1.1          | Ensures every HTTP request has a correlation identifier propagated through requests, responses, and logs.                                                     |
| `tiny-blocks/http-error-handler`   | 2.0.0          | PSR-15 error handler that maps thrown exceptions to structured JSON error responses with optional logging.                                                    |
| `tiny-blocks/http-health-check`    | 1.1.0          | PSR-15 liveness and readiness request handlers with configurable health checks for HTTP services.                                                             |
| `tiny-blocks/http-logging`         | 1.2.0          | PSR-15 middleware that logs HTTP request and response metadata with request duration.                                                                         |
| `tiny-blocks/http-query`           | 2.4.1          | Typed, framework-independent toolkit for HTTP collection queries (RSQL filtering, sorting, and offset and cursor pagination) that never touches a data store. |
| `tiny-blocks/immutable-object`     | 1.1.0          | Provides immutable behavior for objects.                                                                                                                      |
| `tiny-blocks/ksuid`                | 2.0.2          | K-Sortable Unique Identifier.                                                                                                                                 |
| `tiny-blocks/logger`               | 4.0.0          | Emits PSR-3 structured logs for PHP, with correlation tracking, a severity threshold, configurable redaction, and backend-neutral metrics.                    |
| `tiny-blocks/mapper`               | 3.2.1          | Maps PHP objects to and from arrays, JSON, and iterables through reflection and pluggable strategies.                                                         |
| `tiny-blocks/math`                 | 4.0.0          | Arbitrary-precision numbers for PHP, where arithmetic is exact and rounding is explicit.                                                                      |
| `tiny-blocks/outbox`               | 5.1.0          | Write-side adapter for the Transactional Outbox pattern that persists domain events atomically with aggregate state through Doctrine DBAL.                    |
| `tiny-blocks/time`                 | 2.7.0          | Models time as immutable value objects for PHP: instants, durations, periods, timezones, and time-of-day, all UTC-normalized.                                 |
| `tiny-blocks/value-object`         | 5.0.2          | Defines the default behavior contract for PHP value objects with structural equality.                                                                         |
<!-- /tiny-blocks:auto -->

## In-house (`<vendor>`)

The in-house table lists the packages published under the repository's own composer vendor, the `<vendor>` segment of
the `name` field in `composer.json`. It sits outside the auto-generation markers above and is maintained by hand. For a
new organization that publishes none, the table is empty, and that is a normal state: check it, find nothing, and fall
back to the tiny-blocks table or to writing the capability. Reuse before writing holds for these rows exactly as it
holds for the generated table.

The rows below are keyed on capabilities. A row whose token resolves to absent describes a package this project does not
have, so delete it rather than reaching for it.

| Package                 | Capability                                        |
|-------------------------|---------------------------------------------------|
| `<auth-package>`        | JWT authentication and JWKS validation middleware |
| `<idempotency-package>` | Idempotency middleware                            |
