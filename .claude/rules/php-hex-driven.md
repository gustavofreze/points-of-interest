---
description: Driven layer (secondary/outbound adapters). The adapter-kind filename-pattern table plus the contextual transactional-outbox invariants. Driver-vs-Driven naming and the anti-corruption boundary moved to their owners, named inline.
paths:
    - "src/Driven/**/*.php"
---

# Driven layer (secondary adapters)

The `Driven/` layer holds secondary (outbound) adapters: database persistence, external HTTP services, queue and
messaging. This rule keeps the cross-Driven structural residue (the adapter-kind filename pattern) and the contextual
transactional-outbox invariants (see the Outbox section below). The Driver-vs-Driven naming criterion and the
anti-corruption boundary are taught by the skills named inline below.

## Pre-output checklist

1. Adapter filename matches its kind. Bare `Adapter.php` is prohibited. See § Adapter kinds.
2. Outbox adapters follow the publisher and consumer shape, and the spec-owned design when it is documented. An
   integration event publishes only after the aggregate transaction commits. CONTEXTUAL on the service emitting
   integration events. See § Outbox.

## Adapter kinds

Adapter file names follow a strict pattern that signals the adapter kind and lets the matching path-scoped rule load.

| Adapter kind             | Filename pattern                               | Placement                                      |
|:-------------------------|:-----------------------------------------------|:-----------------------------------------------|
| Database persistence     | `<Aggregate>Repository.php`                    | `src/Driven/<Aggregate>/Repository/`           |
| External HTTP service    | `<Provider><Resource>Gateway.php`              | `src/Driven/<Aggregate>/<Concern>/<Provider>/` |
| Low-level HTTP transport | `<Provider>Client.php`                         | beside the `Gateway` it serves                 |
| Queue/messaging          | `<Topic>Publisher.php` / `<Topic>Consumer.php` | `src/Driven/<Aggregate>/Outbox/`               |

The queue row names the classes the service writes itself. Where a package supplies the write side of the outbox, that
package IS the publisher and the service adds no `<Topic>Publisher.php` for it, because a class that only forwards to
the package is a preventive abstraction (`php-design-principles` § Precedence). What the service still owns there is
the anti-corruption layer around the payload, and it takes the shape § Outbox describes.

Every placeholder resolves against the service's own ubiquitous language, never against a sibling service's.
`<Aggregate>` is the aggregate directory the adapter serves, one per aggregate the service persists or integrates.
`<Concern>` names the integration concern grouping the providers of that aggregate, and it is the service's own term for
what the integration does, not a fixed folder name. `<Provider>` is the external party, which may be a third party or
another service in the same organization. Read the sibling directories under `src/Driven/` before adding one: the
service has already chosen its terms, and a new adapter joins them rather than introducing a parallel vocabulary.

The bare `Adapter.php` suffix is prohibited. It is ambiguous (Repository, Gateway, Publisher, and Consumer are all
adapters in the hexagonal sense). Pick the specific pattern matching the adapter's responsibility.

`Client.php` is reserved for thin transport wrappers consumed by a `Gateway.php` in the same folder. The Gateway
implements the outbound port. The Client is an implementation detail of the Gateway when the HTTP plumbing is
non-trivial.

## Driver vs Driven naming

The shapes and how-to are in the php-creating-driven and php-creating-driver skills.

## Anti-corruption layer

The shapes and how-to are in the php-applying-hexagonal-architecture skill.

## Outbox (CONTEXTUAL)

This section applies only when the service publishes integration events through a transactional outbox. The
transactional outbox is a `Driven/<Aggregate>/Outbox/` adapter under the aggregate whose facts it carries, never a
top-level `Driven/Outbox/`. When the specification documents the outbox design, follow it (owned by the spec, read it
via the php-reading-spec skill). For aggregates whose identity comes from a 1:1 counterpart, the
`outbox_events.aggregate_id` is the counterpart's UUID.

An integration event reaches the broker only after the domain events that produced it are committed with the aggregate
change. The outbox guarantees this ordering: the publisher writes the outbox row in the same transaction as the
aggregate, and the consumer publishes only rows that already committed. Never publish an integration event inline from
the handler, ahead of the commit.

The persisted payload is a contract, not a dump of the domain event. A translator maps each domain event to an
integration event carrying primitives and payload types the service owns, and a payload serializer turns that into the
row. Serializing the domain event directly is prohibited: its value objects expose whatever internal state their
libraries happen to hold, which reaches the row as arbitrary structure and changes shape on a library upgrade nobody
reviewed. Where a package supplies the write side, these are the classes the service actually writes, and they live in
the `Driven/<Aggregate>/Outbox/` directory the § Adapter kinds row names, with the integration event types under an
`Event/` subdirectory beside them.

Reading the payload back proves the shape: assert the persisted JSON of at least one fact per aggregate in an
integration test. Asserting only `event_type` leaves the translator untested, because a service with the translator
disabled still writes a row with the right type and the wrong body.

Every domain event the aggregate pushes becomes an outbox row, without exception. A fact with no integration translator
is never discarded: its row persists with the domain event's own contract. Two invariants depend on this. The UNIQUE
`(aggregate_type, aggregate_id, aggregate_version)` is the optimistic-concurrency guard against lost updates, and it
only protects transitions that write a row. And the service dispatches its own follow-up use cases from the facts
published on its channel (the handler-side contract is owned by `php-hex-application-handlers` § Facts drive the flow),
which requires every fact to reach the channel. The service's dispatch queue subscribes to the channel filtered by
`event_type`, and external consumers ignore types they do not consume.

That UNIQUE only works if a reloaded aggregate resumes at the version it last reached, so the repository reconstitutes
it at its persisted version rather than at the initial one. An aggregate rebuilt through its plain constructor restarts
at zero and the next transition rewrites a version the outbox already holds, which turns the concurrency guard into a
constraint violation on the second ordinary write. Where the outbox rows are the only durable record of that counter,
`MAX(aggregate_version)` for the aggregate is the version to restore, read in the same query that loads the aggregate. A
service that prunes its outbox after relaying keeps the counter on the aggregate's own table instead, because the
maximum disappears with the rows.

The in-memory buffer is drained only after the transaction commits. Inside the unit of work the repository pushes the
events it can still see and leaves the buffer intact, and it drains once the commit returns. Draining inside the
transaction loses every fact when the transaction rolls back, because clearing a buffer in memory is not covered by the
database rollback.

On `SIGTERM` a PHP consumer drains: it stops pulling new messages, finishes and acknowledges the message in flight, then
exits. A message cut off mid-processing is never acknowledged, so the broker redelivers it and the consumer's
idempotency absorbs the redelivery. Reuse the transport library's graceful-shutdown facility rather than hand-rolling
signal handling. The drain window is owned by the spec (read it via the php-reading-spec skill).

When the relay is a change-data-capture process (a binlog reader) rather than a PHP consumer, there is no in-process
message to drain: the reader resumes from its last committed position on restart, so this drain shape does not apply.
The publisher still writes to the outbox in the aggregate transaction regardless of how the relay is built.
