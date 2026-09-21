---
name: php-creating-driven
description: Build a Driven (outbound) adapter via the gateway, repository, or outbox sub-flow. Use when calling an external API, persisting an aggregate, or emitting a domain event to a broker.
---

# Add driven adapter

Builds a secondary (outbound) adapter in `src/Driven/`: persistence, external HTTP, or messaging. The adapter always
implements an outbound port and translates the foreign world into the ubiquitous language at the boundary. This skill
routes to the right artifact sub-flow and carries the canonical shapes that the driven rules used to inline.

Build only the adapter the request needs, behind the port that already exists. Start with the simplest shape that works
(a single-class gateway before a two-tier client, the Gateway itself before a cache or retry decorator) and add a layer
only when a transport concern or a second variant actually arrives. The outbound port comes from
`php-creating-application`, never invented here, and a transport concern (retry, cache, circuit breaker) lives in a
decorator over the port, not in the adapter body.

## When to use

- Call an external HTTP API, integrate a provider, add a Gateway over an outbound port.
- Persist an aggregate, add a Repository, write persistence SQL, create a Record.
- Publish an integration event, emit a domain event to a broker, add an outbox publisher or consumer.

## When NOT to use

This skill owns the outbound edge only: the adapter that implements an already-defined port and translates the foreign
world (a provider API, a database row, a broker message) into the domain. It does not define the contract it implements,
and it does not own the inbound side. When the work sits on the other side of the port, reach for the sibling.

- Define the outbound port itself: it is born in `php-creating-application`.
- Emit the domain event itself: that belongs to the aggregate, in `php-creating-application`.
- Create the service HTTP entry (endpoint, webhook, Request DTO): use `php-creating-driver`.
- Build a read-side adapter (Finding, read Queries, Response): use `php-creating-query`, which owns the read stack and
  shares the `Queries` shape with this skill.
- Change schema (new table, column, index, backfill): use `creating-migration`.

## Sub-flows

Pick the artifact path by what the adapter does at the boundary.

| Intent                                             | Sub-flow   |
|:---------------------------------------------------|:-----------|
| Call an external HTTP API, integrate a provider    | gateway    |
| Persist an aggregate to the database               | repository |
| Emit a domain event to a broker (publish, consume) | outbox     |

The gateway translates HTTP to the domain. The repository translates rows to the aggregate. The outbox translates a
domain event into a transport-shaped integration event. Each one implements an outbound port that comes from
`php-creating-application`.

## Rules applied

Load and follow, in this priority. The first rule governs every sub-flow. The middle rules are sub-flow-specific. The
last two apply across the layer.

- `php-hex-driven`: general contract of the outbound adapter, adapter-kind naming, the anti-corruption layer at the
  boundary, and the outbox publisher and consumer invariants (the outbox shape lives here).
- `php-hex-driven-http`: the gateway invariants (Http facade dependency, https, the single isSuccess guard, the
  exception taxonomy, the payload boundary). Idempotency on outbound POST is owned by the spec at `<spec-root>`, read it
  via the `php-reading-spec` skill.
- `php-hex-driven-repository`: Repository, Queries, Record, the row mapping, and the constraint-violation to
  domain-exception translation.
- `php-hex-sql-queries`: bind parameters (no sprintf or interpolation), a mandatory WHERE on DELETE and UPDATE, and the
  utf8mb4 connection charset.
- `php-testing-integration`: test against a real HTTP fixture, the real database, or the real broker.

## Gateway (outbound HTTP) sub-flow

Target: the provider or resource named in the request, resolved against the integrations the service already has under
`src/Driven/`.

### Assembly order

1. Read the spec or the provider contract (`php-reading-spec`).
2. Confirm the outbound port the Gateway implements (comes from `php-creating-application`).
3. Settings, reading env via `fromEnvironment()` with `EnvironmentVariable::from` only, no exception of its own, per
   `php-architecture` § Settings and the `php-hex-driven-http` rule.
4. Gateway implementing the port, translating HTTP to the domain. Start from `references/gateway-single-class.md`.
5. Optional Client for the provider mechanics (`references/gateway-two-tier.md`).
6. Integration test, via `php-writing-tests`.

### Completeness gate

- [ ] Gateway implements the outbound port, never the other way around.
- [ ] Adapter depends on `TinyBlocks\Http\Http`, never a concrete client, per `php-hex-driven-http`.
- [ ] Transport error translated to an application exception through the `<Gateway>Error` enum.
- [ ] Settings reads env via `fromEnvironment()` with `EnvironmentVariable::from` only (never `fromOrDefault`) and
      throws nothing of its own, per `php-architecture` § Settings.
- [ ] Request and response bodies go through payload objects, never built or parsed inline.
- [ ] A cache or retry decorator implements the port, not the Gateway class.
- [ ] Runnable check: the integration suite passes and the style hook is clean.

## Repository (persistence) sub-flow

Target: the aggregate to persist, named in the request (e.g. `Payment`).

### Assembly order

1. Confirm the outbound port the Repository implements (comes from `php-creating-application`).
2. Record: maps the row to the aggregate and reconstitutes without a public factory.
3. Queries: SQL per the sql-queries rules. Start from `references/queries-class.md` and `references/write-sql.md`.
4. Repository implementing the port, with `find` returning nullable.
5. Round-trip integration test, via `php-writing-tests`.

### Completeness gate

- [ ] Repository implements the outbound port, with `find` nullable.
- [ ] `save` maps a violated constraint to its domain exception per `php-hex-driven-repository` (a unique violation to
      the domain exception, e.g. `PaymentAlreadyExists`, a not-null violation to the not-supported exception),
      re-throwing the failure when no mapping applies.
- [ ] Record reconstitutes the aggregate without going through a public factory.
- [ ] An aggregate with `EventualAggregateRootBehavior` has a round-trip test.
- [ ] Every UPDATE and DELETE carries a WHERE clause, per the sql-queries rules. That part is unconditional. Every write
      over a tenant-scoped table also carries that table's tenant discriminator (in the INSERT column list and in the
      WHERE), the multi-tenant isolation invariant, not an optional filter. Look at the table you are writing to, it
      either carries a tenant scope column or it does not, and where the schema alone does not settle which tables are
      tenant-scoped, the spec at `<spec-root>` does, read it via the `php-reading-spec` skill. In a single-tenant
      service, or on a table with no tenant discriminator, there is no scope column to carry and this half of the item
      is skipped rather than failed.
- [ ] Runnable check: the integration suite passes and the style hook is clean.

## Outbox (publisher or consumer) sub-flow

Target: the topic or event named in the request (e.g. `PaymentWasPaid`). This sub-flow has no template yet. Its shape is
owned by the `php-hex-driven` rule (§ Outbox), so read that rule when scaffolding. The ecosystem transport library
enters via `php-reusing-tiny-blocks`, not by hand.

### Assembly order

1. Read the spec of the integration contract (`php-reading-spec`).
2. Check the ecosystem library for the transport (`php-reusing-tiny-blocks`).
3. Publisher: translates the domain event into the integration event and writes to the outbox in the same transaction.
4. Consumer: reads, processes idempotently, acknowledges, and drains on `SIGTERM`.
5. Integration test, via `php-writing-tests`.

### Completeness gate

- [ ] The publishing happens in the aggregate transaction (outbox), not loose outside it.
- [ ] The integration event has a stable transport shape, decoupled from the domain event.
- [ ] The consumer is idempotent.
- [ ] The consumer drains gracefully on `SIGTERM`, CDC relays exempt, per `php-hex-driven` § Outbox.
- [ ] Runnable check: the integration suite passes and the style hook is clean.

## Shapes and how-to (read when scaffolding)

The literal shapes that the driven rules used to inline live here, surfaced only when this skill runs. Each shape below
is named by its path under `php-creating-driven`.

### Design patterns

- For selecting or applying a GoF pattern, use the `php-applying-design-patterns` skill (one cross-cutting catalog,
  usable in any layer). The naming invariant is enforced by `php-code-style` (§ Design-pattern names).

### Gateway shapes

- `references/gateway-two-tier.md`: the `<Provider>Client` plus `<Provider><Resource>Gateway` shape.
- `references/gateway-single-class.md`: the trivial-plumbing single-class variant.
- `references/gateway-error-enum.md`: the `<Gateway>Error` enum (`from(?Code)` then `toException()`).
- `references/payloads.md`: the request and response payload shapes.
- `references/production-binding.md`: the `Dependencies.php` `Http::with(NetworkTransport)` binding.
- `references/gateway-http-howto.md`: timeouts, retry, circuit breaker, caching, logging, the vendor-SDK path, and the
  shared-versus-per-gateway error-enum decision. These are transport concerns that live in `Transport` decorators, never
  in the adapter body.

### Repository shapes

The `Queries` class shape is shared with the read side, so it lives here as the single owner.

- `references/queries-class.md`: the `final readonly class Queries` shape (typed `public const string` constants ordered
  by name length ascending, the SQL body at the 8-space indent, single-quote rule).
- `references/write-sql.md`: the write SQL shapes (INSERT, UPDATE, DELETE), each carrying the table's tenant
  discriminator when the table is tenant-scoped.

### Outbox shapes

No template yet. The publisher and consumer shape is owned by the `php-hex-driven` rule (§ Outbox). Route to that rule
when scaffolding an outbox adapter.

### Read-side cross-reference

For the read-side SQL shapes and the MySQL collation decision tree, see the `php-creating-query` skill
(`references/select.md` and `references/collation-in-comparisons.md`). Those stay in `php-creating-query`.

## Does not do

- Does not define the outbound port or emit the domain event: those belong to the application and domain layer
  (`php-creating-application`).
- Does not build the driver HTTP entry (endpoint, webhook, Request DTO): use `php-creating-driver`.
- Does not build the read-side slice (Finding, read Queries, Response): use `php-creating-query`.
- Does not write the tests itself: it delegates each integration test to `php-writing-tests`.
