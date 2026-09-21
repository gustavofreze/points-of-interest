---
name: php-writing-tests
description: Create tests for an artifact, routing between unit and integration by target type. Use when asked to write, add, or complete tests, raise coverage, kill surviving mutants, or do TDD.
---

# Write tests

Creates tests for an existing artifact, routing the scope by the target. Routes to the testing rules, and carries the
worked shapes and the generative how-to that used to live inside the rules.

Target: the artifact to test, named in the request (e.g. `Payment` or `PaymentRepository`).

Start from the simplest test that drives the behavior. Add machinery only when the target earns it: a Test Data Builder
past five construction parameters, a data provider for a table of cases, never a dedicated test for what the decision
tree rules out.

## When to use

- Write, add, or complete tests, raise coverage, kill surviving mutants, do TDD.

## When NOT to use

- Create the artifact under test: use the layer action, which already delegates here (`php-creating-driver`,
  `php-creating-driven`, `php-creating-query`, `php-creating-application`, or `php-implementing-features`).
- Give a dedicated test to what the decision tree forbids (a value object, enum, DTO, or other non-root covered through
  behavior): walk `references/unit-test-decision-tree.md` first.
- A handler as a unit test: a handler is always integration, per the `php-testing-unit` default stance.
- Documentation or configuration changes with no behavior to exercise.

## Rules applied

Load and follow, in this priority:

- `php-testing`: BDD Given/When/Then, doubles, Test Data Builders, organization, and coverage.
- `php-testing-unit`: what NOT to unit-test and the default stance before creating a class.
- `php-testing-integration`: Fixtures as the single SQL boundary and the round-trip.

## Shapes and how-to (read when scaffolding)

The literal shapes and the generative how-to that the testing rules used to inline live here, surfaced only when this
skill runs:

- `references/bdd-given-when-then.md`: the happy-path and exception Given/When/Then shapes.
- `references/exception-assertion.md`: the prohibited `try`/`catch`-for-message versus the correct
  `expectExceptionMessage`.
- `references/test-data-builder.md`: builder versus inline construction, the command-builder variant, `build()`
  placement, state shortcuts via the real transitions, cover-actions-not-accessors, the `<Aggregate>Builder` skeleton,
  and the two worked usages (creation as the action, creation as setup).
- `references/http-boundary-in-tests.md`: the `InMemoryTransport` plus `Http` facade wiring and the recorded-request
  assertion.
- `references/fixtures.md`: the `Fixtures` shape (the single SQL boundary in tests), the test-class usage, and the
  prohibited inline-SQL shape.
- `references/data-provider.md`: the data-provider shape with sentence-case dataset keys and parameter-named columns.
- `references/exception-raising-behavior.md`: the prohibited exception-as-value-object test versus driving the call path
  that raises it.
- `references/http-boundary.md`: faking the boundary, recording requests (`receivedRequests()`,
  `lastReceivedRequest()`), and `NoMoreResponses` on an exhausted queue.
- `references/coverage-and-mutation.md`: the read-order procedure over the Infection summary, the per-mutant log, and
  `reports/coverage.txt`, with the pass criterion.
- `references/unit-test-decision-tree.md`: the 9-question decision tree, the per-target rulings, the write-versus-read
  endpoint split, when a dedicated non-root test is justified, and testing a domain exception through its raising
  behavior.

## Assembly order

1. Decide the scope by the target. Domain (aggregate root) and WRITE endpoint adapters (`src/Driver/Http/`) go to unit.
   READ endpoint adapters (`src/Query/**/Http/`) go to integration, because a read endpoint's behavior is the query and
   a doubled finding port asserts nothing. Handler, repository, gateway, outbox, and any end-to-end flow go to
   integration too. A handler is never a unit test, per the `php-testing-unit` default stance and
   `references/unit-test-decision-tree.md` item 4.
2. Before creating a unit class, walk the decision tree (`references/unit-test-decision-tree.md`).
3. BDD Given/When/Then, with a single `@When` (`references/bdd-given-when-then.md`).
4. A Test Data Builder when an aggregate or a command has more than five construction parameters
   (`references/test-data-builder.md`).
5. Integration always via `Fixtures`, the single SQL boundary in the tests.
6. Run the scope suite and iterate until it closes (`references/coverage-and-mutation.md`).

## Completeness gate

- [ ] Right path: unit under `tests/Unit`, integration under `tests/Integration`. A read endpoint adapter is in
      `tests/Integration`, never in `tests/Unit` with a doubled finding port.
- [ ] Nothing the decision tree forbids got a dedicated test.
- [ ] An aggregate with `EventualAggregateRootBehavior` has a round-trip integration test.
- [ ] BDD with one `@When`, covering action and invariant, never accessors.
- [ ] Runnable check: the suite passes, coverage and MSI per the rules. Run it through the project's own suite target,
      whatever it is called. Where the canonical `make tests` is absent, use the equivalent the `Makefile` does define
      and report the missing target as a tooling finding, never skip the verification.

## Does not do

- Does not create the artifact under test. The layer action owns that and delegates here.
- Does not give a dedicated test to what the decision tree forbids (value object, enum, DTO, or other non-root).
- Does not unit-test a handler, which is always integration per the `php-testing-unit` default stance.
- Does not exercise documentation or configuration changes that carry no behavior.
