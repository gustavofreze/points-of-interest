---
name: php-creating-application
description: Model the Application inner hexagon (aggregate, value object, enum, domain event/exception/service) and the write use case (command, port, handler). Use when adding any of these in a PHP service.
---

# Add application

Builds the Application layer inner hexagon. Routes between the domain artifacts and the write use case, carries the
canonical shapes and the worked examples that used to live inside the rules, and delegates outward to the adapter skills
when persistence or HTTP enter.

Target: the aggregate, concept, or use case named in the request (e.g. `Payment`, `CreatePayment`).

Build only the artifact the request names. Start with the simplest shape that satisfies the rule (an enum before
per-state classes, a thin handler before a domain service) and add structure when the behavior demands it, not before.
Do not pre-build the outbound adapter, the endpoint, or domain not yet needed: those are sibling skills, reached only
when the request actually crosses into them.

## When to use

- Create a domain artifact: aggregate, value object, enum, domain event, domain exception, or domain service.
- Introduce a write intention (create, authorize, capture, refund, route, and similar) wiring a command, an inbound
  port, a handler, and an outbound port.
- The trigger is not HTTP (outbox consumer, CLI, job), or the endpoint and persistence already exist.

## When NOT to use

This skill owns the inner hexagon only (domain plus the write use-case ring). It stops at the outbound port. The moment
a real adapter, an HTTP entry, or a schema change is what the request needs, the work belongs to a sibling. Reach for
the sibling instead of widening this skill.

- A full vertical slice from HTTP request to the database: use `php-implementing-features`, which orchestrates this
  skill and the adapters in dependency order.
- Only the write HTTP entry (endpoint, controller, webhook, Request DTO): use `php-creating-driver`.
- Persist the aggregate, or build any persistence, gateway, or messaging adapter behind a port: use
  `php-creating-driven`.
- A read-side slice (finding, list, read endpoint): use `php-creating-query`.
- Selecting or applying a GoF pattern in any layer: use `php-applying-design-patterns`.

## Sub-flows

Pick the path by what the request introduces:

- **Domain artifact** (an aggregate, value object, enum, domain event, domain exception, or domain service) with no
  surrounding write flow. Route to § Domain artifact.
- **Use case** (a write intention that wires a command, an inbound port, a handler, and an outbound port). Route to §
  Use case. The use-case path delegates to § Domain artifact for any new domain it needs, never hand-rolling domain
  inside the handler.

When the request is both (a new write flow that also introduces new domain), run § Domain artifact first for the new
concept, then § Use case to wire it.

## Rules applied

Load and follow, in this priority (domain first, then the use-case ring, then patterns and tests):

- `php-hex-application-domain`: aggregate, factories, transitions, events, VOs, enums, exceptions.
- `php-hex-application-handlers`: Handler shape (A, B or C) and the single `handle()`.
- `php-testing-unit`: what to unit-test and what to cover through the aggregate.

The Command, inbound port, outbound port, and domain-service shapes (the `Command` marker with flat primitives, the
gerund inbound port with one `handle()`, the plural-noun nullable outbound port, a domain service only when the rule
crosses aggregates) are carried by this skill's references and completeness gate, the naming by `php-code-style`, the
CQRS fact by `php-architecture`.

## Domain artifact

Models the domain artifacts: the aggregate or concept named in the request.

### Assembly order

1. Read the spec for the aggregate boundary, invariants, transitions, and events (`php-reading-spec`).
2. Value objects and enums, validating in the constructor.
3. Domain exceptions. Start from `references/domain-exception.md`.
4. Aggregate: factories, state transitions, and event emission. Start from `references/aggregate-root.md` and
   `references/state-transition.md`.
5. Domain service, only if the rule crosses aggregates.
6. Unit test of the behavior, via `php-writing-tests`.

### Completeness gate

- [ ] The aggregate drives the behavior, with no anemic setter.
- [ ] A VO validates in the constructor, an invalid transition throws a domain exception.
- [ ] Domain service only where the rule does not fit in an aggregate.
- [ ] Test covers action and invariant, never accessors.
- [ ] Runnable check: the unit suite passes and the style hook is clean.

## Use case

Builds the inner hexagon of a write flow: the use case named in the request.

### Assembly order

1. Read the relevant spec section before generating (`php-reading-spec`). Fix here the Handler shape, whether a new
   outbound port is needed, and whether a domain service is needed. Do not guess. If the spec does not decide, ask
   before generating.
2. Required domain, via § Domain artifact above.
3. Command (write intention, flat primitives), then inbound port (`-ing`, one `handle()`), then outbound port if a new
   collaborator enters. The command never reuses a domain model, value object, enum, or wrapper: primitives only. It
   carries every field of its use case, nullable when optional: a handler never hardcodes `null` for a field the
   transport can supply, and a domain factory never multiplies variants to encode call-site nulls. For a PATCH-style
   partial update, the command carries nullable primitives plus a `providedFields` list naming the use case's changes,
   and the handler dispatches each provided change to an intention-revealing aggregate method (`withPriority`,
   `withCountry`). No layer diffs state: an absent field is simply never dispatched, a provided `null` clears.
4. Handler wiring the three, in the shape decided in step 1. Start from the matching `references/handler-shape-*.md`.
5. Unit test of the behavior, delegated to `php-writing-tests` with unit scope.

### Completeness gate

- [ ] Inbound port in the gerund with a single `handle()`.
- [ ] Command implements the `Command` marker, only flat primitives cross it (scalars and arrays of scalars, never a
      value object or a wrapper such as `Optional`).
- [ ] Outbound port is a plural noun, nullable contract where the row may be absent (the Handler throws the not-found).
- [ ] Handler has a single `handle()`, no business rule that belongs to the aggregate.
- [ ] Domain came from § Domain artifact, not hand-rolled here.
- [ ] Test covers action and invariant, never accessors.
- [ ] Runnable check: the service unit suite passes and the style hook is clean. Coverage and MSI per the testing rules.

## Shapes and how-to (read when scaffolding)

The literal shapes that the rules used to inline live here, surfaced only when this skill runs.

### Design patterns

- For selecting or applying a GoF pattern, use the `php-applying-design-patterns` skill (one cross-cutting catalog,
  usable in any layer). The naming invariant is enforced by `php-code-style` (§ Design-pattern names).

### Domain artifact shapes

- `references/aggregate-root.md`: the eventual-aggregate worked example, the factory composing a child entity and
  emitting the creation event.
- `references/state-transition.md`: the guard-mutate-emit `pay()` and `refund(Reason)` worked example.
- `references/typed-identifier.md`: the `<Aggregate>Id` skeleton over a `Uuid`.
- `references/typed-collection.md`: the `<Collection>` skeleton over the domain `Collection`.
- `references/domain-event.md`: the `<Event>` skeleton with `status()`, `eventType()`, and the reasoned variant.
- `references/domain-exception.md`: the four exception shapes (prohibited native throw, dedicated class, application
  `<Aggregate>NotFound`, input-rejection carrying the raw value).
- `references/aggregate-walkthrough.md`: the narrative behind the two worked examples, eventual versus immutable
  construction and the transition shape.

### Use case shapes

- `references/handler-shape-a-create.md`: Shape A, single aggregate create, with the prohibited build-up-front body kept
  as a paired anti-pattern.
- `references/handler-shape-b-transition.md`: Shape B, single aggregate state transition, loading then applying the
  `for<Origin><Action>` reason factory with the one allowed throwing guard.
- `references/handler-shape-c-orchestration.md`: Shape C, multi-aggregate or compensating body with the one allowed
  `try/catch` fallback.
- `references/handler-branching-antipattern.md`: the create-or-mutate lookup branch, the prohibited body next to the
  factory-decides correction.

## Does not do

- Does not build the write HTTP entry (endpoint, controller, webhook, Request DTO): that is `php-creating-driver`.
- Does not build any driven adapter (persistence, gateway, or messaging behind a port): that is `php-creating-driven`.
- Does not build the read-side slice (finding, list, read endpoint): that is `php-creating-query`.
- Does not author the tests: it delegates unit coverage to `php-writing-tests`.
