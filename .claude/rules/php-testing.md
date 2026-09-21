---
description: Testing conventions. The structural residue only, BDD one-@When discipline, tests mirror src with the Test\ prefix, the real-objects and Spy-suffix double policy, and the 100% coverage and MSI gate. The Given/When/Then, builder, fixture, and HTTP-boundary shapes and the coverage read procedure live in the php-writing-tests skill.
paths:
    - "tests/**/*.php"
---

# Testing conventions

Framework: **PHPUnit**. The worked examples, the Given/When/Then shapes, the builder and fixture templates, the
data-provider shape, and the coverage read procedure live in the `php-writing-tests` skill (`references/`), surfaced
when that skill runs. This path-injected rule keeps the always-on invariants only.

## Pre-output checklist

1. Body uses Given/When/Then doc comments with exactly one `@When` per test. `@Then` (`expectException`) comes before
   `@When` for exception tests. See § One @When per test.
2. Test class lives at the path mirroring its production counterpart under `tests/Unit/` or `tests/Integration/`, the
   namespace mirrors the path with the `Test\` prefix, and the class name ends in `Test`. See § Test paths and
   namespaces.
3. Real objects for collaborators inside the domain. Hand-written doubles use the `Spy` suffix only, never `Mock`,
   `Stub`, `Fake`, or `Dummy`. See § Doubles.
4. 100% line and branch coverage and 100% MSI are required. See § Coverage and mutation.

## One @When per test

Every test uses `/** @Given */`, `/** @And */`, `/** @When */`, `/** @Then */` doc comments, with exactly one `@When`
per test. Two actions require two tests. The single exception is a repeated-invocation test (idempotence, caching,
memoization): when the purpose is asserting that the same operation produces the same outcome across N invocations, the
`@When` block may contain N identical invocations captured in numbered variables (`$first`, `$second`) under a composite
annotation (`@When invoked twice`). When testing that an exception is thrown, place `@Then` (`expectException`) before
`@When`, because PHPUnit requires that ordering. Use `@And` for complementary preconditions or actions to avoid
consecutive `@Given` or `@When` tags.

The happy-path, exception, data-provider, builder, and HTTP-boundary Given/When/Then shapes are in the php-writing-tests
skill.

## Test paths and namespaces

Tests mirror the production layout under `src/` with the equivalent path under `tests/Unit/` (pure domain tests driven
through the aggregate root, and write endpoint adapters) or `tests/Integration/` (handlers, repositories, outbound
adapters, and read endpoint adapters, all against the real infrastructure). The namespace follows PSR-4 from the test
path with the `Test\` prefix, and the class name carries the `Test` suffix. Both the prefix and the suffix are required.

The two endpoint kinds split because their behavior sits in different places. A write endpoint validates, builds a
command, and delegates through an inbound port, so substituting that port with a `Spy` leaves the whole behavior under
test. A read endpoint's behavior IS the query: the projection, the filters, the scope predicate, the pagination. A
double in its place returns whatever it was handed and the test asserts nothing, so a read endpoint is exercised against
a real database, seeded through `Fixtures`, and its test lives under `tests/Integration/`.

- Production: `<RootNamespace>\Application\Domain\Models\Payment`
- Test: `Test\<RootNamespace>\Application\Domain\Models\PaymentTest`

A project whose `autoload-dev` already maps unit tests into the production namespace instead of under `Test\` is
non-conformant, and the fix belongs to the manifest, not to the new file. Report the mapping as a finding, and until it
is corrected write the new test in the mapping the tree actually uses, so one file does not sit alone in a namespace the
autoloader does not resolve. Never split a test suite across two conventions to satisfy this rule.

The per-target unit-versus-integration rulings are in the php-writing-tests skill
(`references/unit-test-decision-tree.md`).

## Doubles

Real objects for collaborators inside the domain. An internal domain collaborator is never substituted with a test
double. Spies appear only at system boundaries (database, HTTP, filesystem, clock). Every hand-written double uses the
`Spy` suffix exclusively (`PublisherSpy`, `GatewaySpy`, `ClockSpy`, `InMemoryPaymentsRepositorySpy`) and records calls
through inspection methods. The suffixes `Mock`, `Stub`, `Fake`, and `Dummy` are prohibited, and any class still ending
in one of them is renamed to end in `Spy` during any review or update pass. Variable and annotation names use the
business concept the object represents (`$publisher`,`$gateway`), never `$spy` or `$mock`.

The builder, fixture, and HTTP-boundary double shapes are in the php-writing-tests skill.

## Coverage and mutation

Line and branch coverage must be 100%, and every mutation reported by Infection must be killed. A line or mutant that
cannot be covered or killed signals a design problem in the production code: refactor the code, never suppress the tool.
The `minMsi` and `minCoveredMsi` thresholds are carried in `infection.json.dist` (owned by `php-skeleton-tooling`), and
the procedure for reading `reports/coverage.txt` and the Infection summary is in the php-writing-tests skill.

Every code-style rule applies to test code without exception, exactly as in production.
