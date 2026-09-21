---
description: Integration test infrastructure. The structural residue only, all raw SQL in tests/ goes through tests/Integration/Fixtures.php, the single class executing DBAL primitives. The Fixtures example, the usage shape, and the round-trip how-to live in the php-writing-tests skill.
paths:
    - "tests/Integration/**/*.php"
---

# Integration tests

Integration tests exercise handlers, repositories, and outbound adapters against the real infrastructure (the service's
`<database-engine>` and, where the service has one, `<messaging-transport>`) with the HTTP boundary faked. The HTTP fake
(`InMemoryTransport` injected through the `Http` facade) is owned by `http-boundary.md` (§ Faking the transport) in the
php-writing-tests skill, over the general boundary-double policy owned by `php-testing` (§ Doubles). They follow every
general testing convention. This rule keeps two invariants: the single SQL boundary in tests and the aggregate
reflection round-trip test.

## Pre-output checklist

1. All database access in `tests/` goes through `tests/Integration/Fixtures.php`. Test classes call its public methods,
   never DBAL primitives directly. See § The Fixtures helper.
2. `Fixtures` is the only class that executes raw SQL. See § The Fixtures helper.
3. An aggregate reconstituted through reflection has a dedicated round-trip integration test. See § Round-trip test.

## The Fixtures helper

`tests/Integration/Fixtures.php` is a `final readonly class` exposed as a test utility. It is the **only** place in
`tests/` that executes raw SQL via `$this->connection->...`. Every integration test class consumes its public methods,
never `executeQuery`, `executeStatement`, `prepare`, or any DBAL primitive directly. The class is named `Fixtures`
instead of `Repository` to avoid colliding with the production `<Aggregate>Repository` classes under
`src/Driven/<Context>/Repository/`.

Its methods are named after the business operation they perform on test data (`insertPayment`,
`countPaymentsByOrganization`, `truncatePayments`, never `runQuery` or `doSelect`), grouped by aggregate (all `Payment`
helpers first, then `Charge`, then `Organization`), and subject to every code-style rule without relaxation. A query
needed by a single test still goes into `Fixtures`: the cost of one extra method is small, the cost of letting raw SQL
spread across `tests/` is large.

The Fixtures example, the test-class usage, and the prohibited inline-SQL shapes are in the php-writing-tests skill.

## Round-trip test

An aggregate whose properties are reconstituted through reflection (matching `state` keys against declared property
names verbatim, for example via `EventualAggregateRootBehavior::reconstituteStrict()`) has a dedicated round-trip
integration test, because a typo in a `state` key is silently dropped with no exception and no warning. The test saves
the aggregate through the repository, reloads it, and asserts every reconstituted property (and that
`aggregateVersion()` equals `MAX(aggregate_version)` from `outbox_events`). A unit test on the aggregate alone does not
exercise the reflection path and does not satisfy this invariant. It guards the production-side reconstitution contract
owned by the domain and repository rules.

The round-trip test steps and the worked shape are in the php-writing-tests skill.
