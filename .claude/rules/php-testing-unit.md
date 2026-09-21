---
description: When to write a unit test and when not. The structural residue only, the default stance (no dedicated test for a non-aggregate-root domain type) and the domain-services exception. The decision tree, per-target rulings, and the exception-raising technique live in the php-writing-tests skill.
paths:
    - "tests/Unit/**/*.php"
---

# Unit tests

Unit tests are pure domain tests driven through the aggregate root. They follow every general testing convention from
`php-testing`. This rule keeps only the default stance on what gets a dedicated unit test. The decision tree to walk
before creating a class, the per-target rulings, the narrow cases where a dedicated non-root test is justified, and the
technique for testing a domain exception through its raising behavior are in the `php-writing-tests` skill
(`references/unit-test-decision-tree.md` and `references/exception-raising-behavior.md`).

## Pre-output checklist

1. Before creating a unit test class, walk the decision tree carried by the `php-writing-tests` skill
   (`references/unit-test-decision-tree.md`). Default to NOT creating the test and covering the behavior through the
   aggregate root. See § Default stance.
2. Domain services are the only non-aggregate-root domain type that gets a dedicated unit test by rule. Every other
   non-root domain type is covered through its aggregate root. See § Default stance.
3. A dedicated unit test for a non-root domain type requires explicit written justification naming the state or path
   unreachable through the aggregate root. See the php-writing-tests skill.

## Default stance

By default, do NOT create a dedicated test class for any non-aggregate-root domain type. Coverage for these types
arrives through aggregate-root tests, integration tests, or endpoint adapter tests. Creating a dedicated test class
requires a written justification (a comment at the top of the file, or the PR description) stating why the condition
cannot be reached through any of those paths.

The single by-rule exception is **domain services** (`src/Application/Domain/Services/`), which get a dedicated unit
test by rule, not by exception. A custom `Redaction` implementation under `src/Driven/Shared/Logging/` gets one too, on
the same footing as the transactional-wrapper carve-out: it carries branching logic, and its only consumer is the
structured logger built at the composition root, which is coverage-excluded, so no endpoint, handler, or repository path
reaches it. The catalog that merely lists which redactions the service installs carries no branching and is declarative
wiring, so it stays inline in `src/Dependencies.php` and is never a class of its own.

Aggregate roots and write endpoint adapters (`src/Driver/Http/`) also get dedicated tests here. A READ endpoint adapter
(`src/Query/**/Http/`) gets a dedicated test too, but not in this tree: its behavior is the query, so it is exercised
against a real database and its test lives under `tests/Integration/` (php-testing § Test paths and namespaces). Every
other target is routed elsewhere by the decision tree in the php-writing-tests skill (handlers, repositories, and
outbound gateways to `tests/Integration/`, shared driver infrastructure to the endpoint adapter tests, root-level
bootstrap files and every env-only Settings class to the `phpunit.xml` `<exclude>` block, wherever that Settings class
lives).
