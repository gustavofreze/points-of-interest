---
description: HTTP rules for the inbound side (Driver/Http and Query). The structural residue only, the Driver/Http folder structure and the exception-to-HTTP boundary invariant (domain exceptions stay pure, translation only at the boundary, the {code,message}/SCREAMING_SNAKE envelope, the 422/409/404 decision test). Shapes and tables moved to their owners, named inline. Auth, pagination, idempotency, rate limiting, and content negotiation each carry a portable default that the spec overrides where it speaks.
paths:
    - "src/Driver/Http/**/*.php"
    - "src/Query/**/*.php"
---

# HTTP (inbound)

Rules for the inbound side of the hexagon: HTTP endpoints under `src/Driver/Http/` and the read side under `src/Query/`.
Client-side HTTP (adapters under `src/Driven/` that call external APIs) is a separate, outbound concern owned by
`php-hex-driven-http`.

This rule keeps only the always-on structural residue: the `Driver/Http/` folder structure and the exception-to-HTTP
boundary invariant. The mapping code shape moved to its owner. The authentication contract, pagination, idempotency,
rate limiting, and content negotiation each carry a portable default below, which the spec at `<spec-root>` overrides
where it speaks, so every one of them resolves to something with or without a spec.

## Pre-output checklist

1. Domain exceptions stay pure. No HTTP codes inside them. HTTP translation happens only at the `Driver/Http/` and
   `Query/Shared/Http/` boundary via `ExceptionMapping`. See § Exception to HTTP mapping.
2. The decision between `422`, `409`, and `404` follows the "would the same request fail again with no server-side
   changes?" test. See § Decision test.
3. Error responses use the `{"code": "...", "message": "..."}` envelope. `code` is `SCREAMING_SNAKE_CASE`.
   `INVALID_REQUEST` is reserved for validation failures. See § Error response structure.
4. The `Driver/Http/` layout (`Endpoints/`, `Validations/`, `Middlewares/`, root-level mapping) is respected. See §
   Driver/Http folder structure.

## Driver/Http folder structure

The `Driver/Http/` layer separates endpoint code from shared HTTP infrastructure:

1. **`Endpoints/<Entry>/`** holds **only** endpoint adapter classes, their `*Request.php` DTOs, and the entry's own
   dispatcher when its translation fans out to more than one inbound port. The subdirectories segregate by entry nature,
   three kinds, and a service carries only the kinds it actually has: the API entries, one directory per resource of the
   service's own bounded context (covering caller operations and sweep commands alike), the provider notifications under
   `Webhook` (§ Webhook entry), and the domain facts a bounded context delivers over HTTP under `<Context>Event`. A
   `<Name>Dispatcher.php` is translation only (payload vocabulary to command to inbound port), carries no business rule,
   and colocates with the endpoint it serves. Ordinary endpoint adapter class names are the operation verb-noun WITHOUT
   an `Endpoint` suffix: `Create<Resource>.php`, `Capture<Resource>.php`, `Refund<Resource>.php` (NOT
   `Create<Resource>Endpoint.php`). The read side follows the same convention (`Find<Resource>ById.php`, NOT
   `Find<Resource>ByIdEndpoint.php`).
2. **`Validations/`** holds shared HTTP validation infrastructure (`RequestValidator`, `RequestPayload`, `RequestField`,
   `ValidationRules`, `RequestFieldTree`, and any other shared validator helper). Validation infrastructure NEVER lives
   under `Endpoints/`.
3. **`Middlewares/`** holds PSR-15 middlewares.
4. **`Driver/Http/` root** holds cross-cutting infrastructure consumed across all endpoints:
   `<Stack>ExceptionMapping.php` and `InvalidRequest.php`. Error handling is delegated to the library `ErrorMiddleware`,
   composed once at the composition root in `src/Dependencies.php` (the `driver` layer). The `Routes` file only adds the
   built middleware. None of these files live inside `Endpoints/`, `Validations/`, or `Middlewares/`.

### Webhook entry

`Endpoints/Webhook/` is the carve-out to both the suffix-less naming and the closed file list of item 1, because an
inbound notification is named after its provider rather than a verb from this service's ubiquitous language, and because
every provider posting one repeats the same verify-normalize-dispatch flow.

`Endpoints/Webhook/` holds a single provider-agnostic `WebhookEndpoint.php`, the only endpoint class of the entry,
keeping the `Endpoint` suffix. It resolves the provider from the route parameter (`/v1/webhooks/{provider}`) through a
registry, so a new provider is a wiring entry in `src/Dependencies.php` and never a new endpoint. Besides it live the
provider-agnostic pieces that flow needs, and no others: the registry and its entry type, the contracts each provider
implements (the signature validator and the notification normalizer), the normalized notification vocabulary those
contracts return, the entry's dispatcher, and the signature exception.

`Endpoints/Webhook/<Provider>/` holds that provider's implementations of the contracts and the payload types they parse,
each named after the provider (`<Provider>SignatureValidator.php`, `<Provider>Notifications.php`,
`<Provider>WebhookSettings.php`). A provider directory holds no endpoint class.

## Exception to HTTP mapping

The `Driver/Http` and `Query/Shared/Http` layers are the only place where the service's own exceptions are converted to
HTTP responses. Domain exceptions stay pure: they carry no HTTP code and no formatted message (owned by
`php-hex-application-domain` § Exception layer placement). Every domain exception in
`src/Application/Domain/Exceptions/` and every application exception in `src/Application/Exceptions/` (including the
gateway failures owned by `php-hex-driven-http`) has an explicit `->when(exceptionClass:)` arm in its vertical's
`<Stack>ExceptionMapping`. Third-party and library exceptions are never given an arm, they fall through to
`500 INTERNAL_ERROR` like any unmapped throwable. A handler never catches a domain exception to re-throw it as a
different one. A new domain or business exception adds its mapping arm, its OpenAPI `examples`, and its docs entry in
the same change.

The one carve-out is a domain guard that a request-validation rule fully shields, so it can never surface through any
endpoint. Giving it a mapping arm would add an endpoint-unreachable branch that no test can exercise (dedicated mapping
tests are prohibited), so it survives mutation and reads as dead code, both forbidden by the 100 percent mutation and
no-dead-code rules. Such a guard is covered by its domain unit test alone and stays out of the mapping. A guard
qualifies only when a request-validation rule provably shields every endpoint that can reach it, so the instances are
read from the service's own validations and domain tests, never from a list kept here.

The `ExceptionMapping` code shape (`mappings()`, `mapsTo` versus `resolvesWith`) is in the php-creating-driver skill
(`references/exception-mapping.md`).

### Decision test

Ask: **"If I sent the exact same request again with no server-side changes, would it still fail?"**

- **Yes**, the input itself is invalid, so `422`.
- **No**, the failure depends on server state, so `409` (or `404` when the resource is missing).

### Error response structure

```json
{
    "code": "SCREAMING_SNAKE_CASE_ERROR_CODE",
    "message": "Human-readable description."
}
```

The `code` is derived from the exception class name in `SCREAMING_SNAKE_CASE`. `INVALID_REQUEST` is reserved for generic
input validation failures at the command and request level. That envelope is the default and it holds on every error
response, 4xx and 5xx alike, with no field beyond `code` and `message`, so checklist item 3 always has a defined
envelope to check against. The canonical code values are owned by the spec at `<spec-root>`, read it via the
php-reading-spec skill, and the spec overrides this default where it speaks. With `<spec-root>` unset the envelope above
is the whole contract, and the service's own `openapi.yaml` is the register of its code values.

## Authentication and authorization

The default: credentials are validated in a PSR-15 middleware wired once on the route group, never inside an endpoint,
so an endpoint only ever sees an already-authenticated caller. A caller with no valid credential gets `401`, an
authenticated caller lacking permission gets `403`, and neither status is ever produced by domain code. The package
supplying that middleware is the auth row of the `php-skeleton-tooling` dependency table, the single place that binding
lives, and it is not named again here. The token format, the claims, and the authorization model are owned by the spec
at `<spec-root>`, read it via the php-reading-spec skill, and the spec overrides this default where it speaks. With
`<spec-root>` unset the middleware placement and the 401 versus 403 split still hold.

## Pagination

The default: a collection response is a `{data, meta, links}` envelope, keyset (forward-only cursor) for tenant-scoped
operational records and offset with full navigation for the finite administrable catalogs. A singular resource, a
derived list, and a batch lookup are not paginated and carry neither `meta` nor `links`. The concrete parameter names,
the envelope fields, and the archetype per collection are owned by the spec at `<spec-root>`, read it via the
php-reading-spec skill, and the spec overrides this default where it speaks. The per-slice keyset and offset query
shapes are in the php-creating-query skill.

## Idempotency

The default: a write endpoint that accepts an `Idempotency-Key` header replays the stored response when the same key
arrives with the same request body, answers `409` when the same key arrives with a different body, and rejects the
request when the route declares the header mandatory and it is absent. The guarantee is applied in a middleware, never
inside a handler, and the key is a value stable across retries and never regenerated per attempt. The header name, the
key format, the retention window, and which routes require it are owned by the spec at `<spec-root>`, read it via the
php-reading-spec skill, and the spec overrides this default where it speaks. The package supplying the middleware is the
idempotency row of the `php-skeleton-tooling` dependency table.

## Rate limiting

The default: throttling is an edge concern and no endpoint implements it. The application enforces a limit only when the
limit is itself a business rule (a login throttle, a per-tenant quota), and it then answers `429`. The limits, the
buckets, and the layer that enforces each one are owned by the spec at `<spec-root>`, read it via the php-reading-spec
skill, and the spec overrides this default where it speaks.

## Content negotiation and CORS

The default: the service speaks `application/json` on both request and response and negotiates nothing else. CORS is an
edge concern owned by whatever gateway fronts the service, so no endpoint and no middleware here emits an
`Access-Control-*` header. The allowed origins and any additional media type are owned by the spec at `<spec-root>`,
read it via the php-reading-spec skill, and the spec overrides this default where it speaks.

## Tests

Owned by `php-testing-unit` (the no-dedicated-mapping-test prohibition) and the php-writing-tests skill.
