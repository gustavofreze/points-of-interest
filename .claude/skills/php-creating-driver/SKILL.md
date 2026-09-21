---
name: php-creating-driver
description: Build a Driver (inbound) HTTP adapter and own the write error stack, via the endpoint or webhook sub-flow. Use when creating a write endpoint, route, controller, webhook, or Request DTO.
---

# Add driver adapter

Builds the inbound (primary) HTTP entry under `src/Driver/Http/` and owns the write error stack. This skill routes
between a regular write endpoint and a provider webhook, then points back to the driver HTTP rule for the endpoint and
Request placement (§ Driver/Http folder structure).

Start with the thinnest entry that validates and delegates to the inbound port. Add an `ExceptionMapping` arm, a new
exception, or webhook idempotency machinery only when a real exception or a real replay enters, never preventively.
Prefer a regular endpoint, reach for a webhook only when a provider initiates the call.

## When to use

- Create a write endpoint, a route, a controller, or a Request DTO.
- Add or extend a provider webhook handler (signature verification, fast acknowledgement, idempotency).
- Add or extend the write error stack (the `Driver/Http` `ExceptionMapping` and `InvalidRequest`).

## When NOT to use

- A full vertical slice from HTTP request to the database: use `php-implementing-features`.
- A read endpoint, list, or find: use `php-creating-query`.
- Implement the Handler or the Command behind the endpoint: use `php-creating-application`.
- Persist or write SQL behind the outbound port: use `php-creating-driven`.
- Change schema: use `creating-migration`.

## Sub-flows

Pick the path by who initiates the request.

| Intent                                                                  | Sub-flow |
|:------------------------------------------------------------------------|:---------|
| A client calls the service to perform a write (create, capture, refund) | endpoint |
| A provider posts an inbound notification (callback) to the service      | webhook  |

Both sub-flows live under `Endpoints/<Context>/`. The regular-endpoint naming, the provider-agnostic `WebhookEndpoint`
entry, and the per-provider contract files are owned by `php-hex-driver-http` (§ Driver/Http folder structure), read it
when scaffolding. The command an endpoint dispatches comes from `php-creating-application`. A read endpoint is not built
here, it comes from `php-creating-query`.

## Rules applied

Load and follow, in this priority:

- `php-hex-driver-http`: endpoint, Request, webhook, the write error stack, the endpoint and Request placement (§
  Driver/Http folder structure), auth, pagination, idempotency, content negotiation, and CORS. The `ExceptionMapping`
  shape is in `references/exception-mapping.md` here.
- the `php-writing-documentation` skill: the endpoint enters the docs page and `openapi.yaml`. `web-documentation` owns
  the cross-file sync and prose style.
- `php-testing-unit`: exhaustive test of the endpoint adapter, with no dedicated `ExceptionMapping` test.

## Endpoint (write) sub-flow

Target: the route or use case named in the request (e.g. `CreatePayment`).

### Assembly order

1. Read the spec of the route or contract (`php-reading-spec`).
2. Request DTO and input validation, using the shared `Validations/` helpers.
3. Endpoint adapter, delegating to the inbound port (the Handler) from `php-creating-application`, with no business
   rule.
4. Write error stack (the `Driver/Http` `ExceptionMapping`, the composed `ErrorMiddleware` binding in
   `src/Dependencies.php`, `InvalidRequest`), if new. A write endpoint adds its exception arm to the `Driver/Http`
   mapping, never the read-side `Query/Shared/Http` mapping.
5. Endpoint docs per the documentation rule, kept in sync with `openapi.yaml`.
6. Unit test covering validation, success, and error translation, via `php-writing-tests`.

### Completeness gate

- [ ] Endpoint only validates and delegates, with no business rule.
- [ ] Adapter imports only inward (Application ports, commands, Application or Domain types), never a `Driven\*` or a
      `Query\*` type.
- [ ] New domain or application exception gets its arm in the `Driver/Http` `ExceptionMapping`, plus an OpenAPI example,
      plus a docs entry, all in the same change.
- [ ] Status codes come from `tiny-blocks/http`, never a magic number.
- [ ] Every validation and error-translation branch covered by a unit test.
- [ ] Endpoint docs updated.
- [ ] Runnable check: the unit suite passes and the style hook is clean.

## Webhook sub-flow

Target: the provider whose callback arrives. The shared entry (the `WebhookEndpoint`, the registry, the contracts, the
normalized notification, the dispatcher) already lives at `Endpoints/Webhook/`, so adding a provider means implementing
the contracts and registering the provider, never writing an endpoint.

### Assembly order

1. Read the spec of the provider callback contract (`php-reading-spec`).
2. Payload types for the provider notification, under `Endpoints/Webhook/<Provider>/`.
3. `<Provider>WebhookSettings` and `<Provider>SignatureValidator`, implementing the signature contract.
4. `<Provider>Notifications`, normalizing the provider payload into the shared notification vocabulary and returning
   null when the payload tracks no transition.
5. Register the provider in the registry binding in `src/Dependencies.php`.
6. Write error stack: add the `InvalidWebhookSignature` arm to the `Driver/Http` `ExceptionMapping`, if new.
7. Endpoint docs per the documentation rule.
8. Unit test covering signature failure, idempotent replay, success, and error translation, via `php-writing-tests`.

### Completeness gate

- [ ] Webhook verifies the signature and is idempotent.
- [ ] The provider enters as a registry entry, with no new endpoint class.
- [ ] A replay of the same notification produces no duplicate side effect.
- [ ] Adapter imports only inward, never a `Driven\*` or a `Query\*` type.
- [ ] Signature failure maps through `InvalidWebhookSignature` in the `Driver/Http` `ExceptionMapping`.
- [ ] Every validation and error-translation branch covered by a unit test.
- [ ] Endpoint docs updated.
- [ ] Runnable check: the unit suite passes and the style hook is clean.

## Shapes and how-to (read when scaffolding)

- `references/exception-mapping.md`: the `<Stack>ExceptionMapping` code shape (the `final readonly` mapping, the
  `mappings()` table, `mapsTo` versus `resolvesWith`).
- For GoF pattern selection, use the `php-applying-design-patterns` skill (cross-cutting). The naming invariant is in
  `php-code-style` (§ Design-pattern names).
- Endpoint and Request placement, plus the `Endpoints/<Context>/` and webhook layout: `php-hex-driver-http` (§
  Driver/Http folder structure).
- The 422 versus 409 versus 404 choice and the `{code, message}` error envelope: `php-hex-driver-http` (§ Decision test,
  § Error response structure).
- Auth, pagination, idempotency, content negotiation, and CORS: the matching sections of the same rule. The endpoint and
  Request code shapes are not extracted yet: when they are, they move under `references/` here and this section points
  to them.

## Does not do

- Does not orchestrate the full vertical slice across layers: use `php-implementing-features`.
- Does not build the read side (a read endpoint, list, or find): use `php-creating-query`.
- Does not implement the Handler or the Command behind the endpoint: use `php-creating-application`.
- Does not persist or write SQL behind the outbound port: use `php-creating-driven`.
- Does not change schema: use `creating-migration`.
