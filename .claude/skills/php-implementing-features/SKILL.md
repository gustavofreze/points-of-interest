---
name: php-implementing-features
description: Orchestrate the vertical slice in dependency order with one spec read and a completeness gate. Use when implementing a feature or endpoint end to end, or any change crossing more than one layer.
---

# Implement feature

Orchestrates the vertical slice. Does not apply rules directly: each step delegates to the layer action, which carries
its own rules. The role here is assembly order, a single spec read, and the gate.

Start with the thinnest slice the spec demands. Add a layer only when the use case reaches it, never scaffold a layer
the slice does not need. Complexity earns its place as the request grows. It is not poured in up front.

Target: the feature or use case named in the request.

## When to use

- The request goes from an entry point (HTTP, webhook) to persistence, crossing more than one layer.
- Implement a use case or endpoint end to end.

## When NOT to use

- It touches a single layer: use the specific layer skill (`php-creating-application`, `php-creating-driven`,
  `php-creating-driver`, `php-creating-query`).
- The change is read-only (a list or find with no write): go straight to `php-creating-query`.
- The work is a behavior-preserving refactor or rename inside one layer: the layer skill owns it, no orchestration is
  warranted.
- The question is only what the spec says: read it with `php-reading-spec` and stop there.

## Rules applied

None directly. Each step delegates and the called action carries its rules. At the close, confirm the
`php-writing-documentation` skill (with `web-documentation` for sync and style) and the testing rules (`php-testing`,
`php-testing-unit`, `php-testing-integration`).

## Assembly order

1. Read the spec once (`php-reading-spec`). Fix the scope and what is out of it.
2. Application inner hexagon (the domain plus the use-case command, inbound port, handler, and outbound port):
   `php-creating-application`.
3. Migration, if there is new schema: `creating-migration`.
4. Driven adapters (persistence repository, outbound gateways, outbox), each implementing an application outbound port:
   `php-creating-driven`.
5. Driver HTTP entry (endpoint or webhook): `php-creating-driver`.
6. Read-side, if the request includes a query: `php-creating-query`.
7. Unit and integration tests: `php-writing-tests`.

## Completeness gate

- [ ] Every layer within scope exists and passed its action gate.
- [ ] Nothing outside the defined scope was touched. The scope comes from the spec at `<spec-root>`, and with that
      unset it comes from the request itself, so the item holds either way and is never skipped.
- [ ] Runnable check: the full suite (unit and integration) passes and the style hook is clean.
- [ ] Coverage and MSI per the testing rules. On a large slice, close with `php-reviewing-conformance`.

## Does not do

- Does not widen scope beyond what the spec defined.
- Does not skip a layer gate to check later.
