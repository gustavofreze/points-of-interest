# Unit-test scope decision tree (read before creating a test class)

Read this while deciding whether a target gets a dedicated unit test. None of it is a per-file invariant, so it is NOT
path-injected. The default stance and the kept invariants live in `php-testing-unit`. This file holds the decision
support that used to sit inside that rule.

Unit tests are pure domain tests driven through the aggregate root. By default, do NOT create a dedicated test class for
any non-aggregate-root domain type. Coverage arrives naturally through aggregate-root tests, integration tests, or
endpoint adapter tests. The single exception by rule (the closed set is owned by `php-testing-unit`) is **domain
services** (`src/Application/Domain/Services/`).

## Contents

- Decision tree (consult before creating any test class)
- Details
- When a dedicated test IS justified
- Testing a domain exception through the raising behavior

## Decision tree (consult before creating any test class)

Before writing a new test class, walk these questions in order. The first match dictates the answer.

1. **Is the target under `src/Driver/Http/`?**
    - If it is an `*Endpoint.php` (or its `*Request.php` DTO), write the endpoint adapter test, a **unit** test at the
      mirrored `tests/Unit/` path. A write endpoint delegates through an inbound port, so a `Spy` in that port's place
      leaves the endpoint's whole behavior (validation, command construction, status mapping) under test.
    - Otherwise, NO TEST. It is shared driver infrastructure (validators, middlewares, exception mapping, error
      handlers). Covered by the endpoint adapter tests that exercise it. See "Shared driver infrastructure" below.
2. **Is the target under `src/Query/`?**
    - If it is an `*Endpoint.php` under a `Http/` folder (or its `*Request.php` DTO), write the endpoint adapter test
      as an **integration** test at the mirrored `tests/Integration/` path, extending `IntegrationTestCase` and seeding
      through `Fixtures`. Never a unit test with a doubled finding port: a read endpoint's behavior IS the query (the
      projection, the filters, the scope predicate, the pagination), so a double returns whatever it was handed and the
      test asserts nothing. See "Read endpoint adapters" below.
    - If it is a read model, a mapper, or a `Queries` class, it carries no dedicated test. It is covered through the
      read endpoint adapter test that exercises it.
3. **Is the target under `src/Driven/`?**
    - If it is the canonical transactional-wrapper abstraction under `src/Driven/Shared/Database/`
      (in this service, `RelationalConnection.php`), write a **unit** test at the mirrored
      `tests/Unit/` path. It is a thin transactional wrapper around
      `Doctrine\DBAL\Connection` whose contract (open transaction, commit on success, rollback on exception) is
      exercised by verifying the calls made to a Spy connection. No real database is needed. Do NOT create an
      integration test for it. Integration coverage of transactional behavior arrives through any repository test that
      uses it.
    - If it is a custom `Redaction` implementation under `src/Driven/Shared/Logging/`, write a **unit** test at the
      mirrored `tests/Unit/` path. It carries branching logic, and its only consumer is the structured logger built at
      the composition root, which sits in the `phpunit.xml` `<exclude>` block, so no endpoint, handler, or repository
      path reaches it. The catalog naming which redactions the service installs is a different thing: it carries no
      branching, it is declarative wiring, and it stays inline in `src/Dependencies.php` rather than becoming a class.
      This and the transactional wrapper are the two unit-test exceptions under `src/Driven/`.
    - Otherwise, NO unit test. Cover it with an integration test against the real external system. For an outbound HTTP
      gateway the "real external system" is faked with
      `TinyBlocks\Http\Client\Transports\InMemoryTransport` injected through the `Http` facade (mechanism owned by
      `http-boundary.md` § Faking the transport, general double policy by
      `php-testing` § Doubles, production seam by `php-hex-driven-http` § HTTP transport dependency). A pure-logic
      `src/Driven/` collaborator with no external system (a routing or strategy-selection dispatcher, for instance) is
      still NOT a unit-test target. Its behavior is covered by the integration test of the handler or flow that runs the
      real port chain with only the transport faked. The justification escape hatch in "When a dedicated test IS
      justified" is for domain types (value objects, enums) only, never for a `src/Driven/` type. The sole dedicated
      tests under `src/Driven/` remain `RelationalConnection` (Exception B), the constraint-translation branch
      (Exception A), and the custom `Redaction` implementations under `src/Driven/Shared/Logging/` (Exception C).
      See "Driven adapters" below.
4. **Is the target under `src/Application/Handlers/`?**
    - **NO unit test.** Cover it with an integration test that exercises the full domain plus database path.
5. **Is the target a file directly under `src/` (root, no subdirectory)?**
    - **NO TEST.** Files at the root of `src/` are bootstrap and infrastructure declarations (DI registration, routing,
      settings, application kernel). They contain no business logic. Add the file to the `<exclude>` block in
      `phpunit.xml`. Examples: `src/Dependencies.php`,
      `src/Routes.php`, `src/DatabaseSettings.php`.
6. **Is the target a `<Name>Settings.php` class inside a subdirectory of `src/` (i.e., not caught by item 5) whose only
   logic is reading environment variables?** The canonical shape is the Settings contract.
    - **NO TEST.** Settings classes inside subdirectories of `src/` are eligible for `<exclude>`
      when their only logic is the `fromEnvironment()` factory plus optional methods that look up env vars by
      dynamically built keys (e.g., `destinationFor(string $eventType)`). Reading an env var and returning the value,
      directly or through a dynamic key, is the same pattern. A unit test for this is tautology (set env, instantiate,
      read, assert). Add the file to
      `<exclude>`.
    - **WRITE THE TEST** when the Settings class contains methods with calculation, transformation, validation, or
      cross-field dependencies. That is real behavior, not env reading. The class then leaves the exclusion list and
      follows the regular test path through its consumer.
7. **Is the target a domain type other than an aggregate root?** (value object, enum, internal entity, domain exception,
   command)
    - **NO unit test by default.** Coverage arrives through the aggregate root that consumes it. See "Domain types
      covered through the aggregate" below for the narrow exceptions.
8. **Is the target a domain service under `src/Application/Domain/Services/`?**
    - **WRITE A DEDICATED UNIT TEST** under `tests/Unit/Application/Domain/Services/`. Domain services are pure
      functions over domain objects (no I/O, stateless) and have a public contract worth testing in isolation. This is
      an explicit exception to item 7: domain services are not internal domain types, they are domain operations. The
      structural contract is owned by the domain rules.
9. **The target is an aggregate root or an endpoint adapter** : write the test.

## Details

**Shared driver infrastructure**: every class under `src/Driver/Http/` that is NOT an endpoint adapter or its
`*Request.php` DTO. This is the entire content of:

- `src/Driver/Http/Validations/`. `RequestValidator`, `RequestPayload`, `RequestField`, `ValidationRules`,
  `RequestFieldTree`, and any other shared validator helper.
- `src/Driver/Http/Middlewares/`. Every middleware class.
- The cross-cutting infrastructure files at the `Driver/Http/` root: `DriverExceptionMapping.php` and
  `InvalidRequest.php`.

Their behavior is covered by the endpoint adapter tests that exercise each validation branch, each exception-to-HTTP
translation, and each middleware-wrapped request. A dedicated test for any of these classes duplicates coverage and
bypasses the real execution path. Any existing `DriverExceptionMappingTest`, `QueryExceptionMappingTest`,
`RequestValidatorTest`, or similar test MUST be deleted. Its scenarios belong in the corresponding endpoint tests.

Each exception mapping (the write-side `DriverExceptionMapping` and the read-side `QueryExceptionMapping`, consumed by
the shared `ErrorMiddleware` from `tiny-blocks/http-error-handler`) reaches 100% line, branch, and mutation coverage
exclusively this way. The endpoint test builds the real concrete handler over spied outbound ports, wraps it in
`ErrorMiddleware::create()->withMapping(mapping: new DriverExceptionMapping())->build()`, drives a request that makes
the handler raise the exception under test, and asserts the translated HTTP status and the error code. Every mapping arm
and its `resolvesWith` message template is exercised the moment an endpoint test reaches the call path that raises its
exception. A mapping arm with no endpoint test reaching its exception is uncovered code, closed by adding the missing
endpoint scenario, never by a `DriverExceptionMapping` or `QueryExceptionMapping` test of its own. The HTTP-side
normative detail is owned by `php-hex-driver-http` (§ Tests).

The same applies to `src/Driver/Http/Endpoints/<Context>/<Name>Request.php` and `<Name>Response.php` types themselves.
Their coverage lives in the endpoint test that deserializes the HTTP payload through the real validator and serializes
the response back. A dedicated `<Name>RequestTest` or `<Name>ResponseTest` asserts only round-trip accessor behavior,
which is a tautology.

**Read endpoint adapters** (`src/Query/**/Http/<Name>.php`). The one endpoint kind that is an integration test, and the
reason is the shape of the thing rather than a preference. A write endpoint validates, builds a command, and hands it to
an inbound port, so the port is a real seam: put a `Spy` there and the endpoint's own behavior stays under test. A read
endpoint has a finding port too, but nothing worth testing sits on this side of it. The projection, the joins, the
filters, the scope predicate, the ordering, the pagination cursor are all in the SQL behind that port, so a double
returns whatever the test handed it and the assertion is a tautology. Write the test under the mirrored
`tests/Integration/` path, extend `IntegrationTestCase`, seed through `Fixtures`, and drive the endpoint over the real
HTTP boundary.

Everything else under `src/Query/` follows from that: read models, mappers, `Queries` classes, and the read-side
`ExceptionMapping` carry no dedicated test, because the read endpoint test that exercises each branch already covers
them. This mirrors how shared driver infrastructure is covered on the write side.

**Driven adapters**: repositories under `src/Driven/<Context>/Repository/<Aggregate>Repository.php`,
domain-to-infrastructure translators, outbound HTTP clients, webhooks, external API adapters. They are covered
exclusively in `tests/Integration/`, with the database and message bus real and the HTTP boundary faked through
`InMemoryTransport` (owned by `http-boundary.md` § Faking the transport, general double policy by `php-testing` §
Doubles). **Exception A**: an adapter that raises a domain exception directly from a constraint translation (e.g.,
`UniqueConstraintViolationException` translated to `PaymentAlreadyExists`) gets a dedicated unit test covering that
specific translation branch, and only that branch. The external-system path still requires its own integration test,
which remains mandatory. **Exception B**: the canonical transactional wrapper around Doctrine's `Connection` under
`src/Driven/Shared/Database/` (in this service, `RelationalConnection.php`). Is covered by a dedicated **unit** test at
the mirrored `tests/Unit/` path using a Spy of `Doctrine\DBAL\Connection`. It MUST NOT have an integration test of its
own. Transactional behavior in the live database is observed through any repository integration test that uses the
connection. **Exception C**: a custom `Redaction` implementation under `src/Driven/Shared/Logging/`. It carries
branching logic, and the structured logger that consumes it is built at the composition root, which sits in the
`phpunit.xml` `<exclude>` block, so no endpoint, handler, or repository path reaches it. It gets a dedicated **unit**
test at the mirrored `tests/Unit/` path and no integration test. The catalog listing which redactions the service
installs is not a class: it carries no branching, it is declarative wiring, and it stays inline in
`src/Dependencies.php`.

**Handlers** (`src/Application/Handlers/`). Thin orchestrators whose behavior is meaningful only with real collaborators
(domain aggregate plus database). Cover them in `tests/Integration/` exclusively. Remove any
`tests/Unit/Application/Handlers/` folder if it exists.

**Domain types covered through the aggregate**:

- **Value objects used exclusively by a single aggregate**: the VO's invariants (e.g., "name must not be empty") are
  exercised when the aggregate factory accepts an invalid primitive and the VO constructor throws. **Exception**: a VO
  may have a dedicated test when it exposes non-trivial public behavior that cannot be reached through the aggregate's
  public API. For example, a rich parsing method, arithmetic, or a comparison operator. A VO with only a private
  constructor and a `from()` / `toString()` pair does NOT qualify.
- **Enums in the domain layer**: backed enums with methods are covered through the aggregate that exercises each case.
  **Exception**: a dedicated enum test is justified ONLY when the enum has non-trivial standalone methods (e.g.,
  `PaymentStatus::canTransitionTo(PaymentStatus $other): bool` with branching logic).
- **Internal entities** (non-root entities owned by an aggregate). Covered through the aggregate root. Dedicated tests
  only when a specific invariant is structurally unreachable through the root's public API. And at that point, the
  design is suspect: ask first whether the invariant belongs on the root.
- **Domain exceptions** (`src/Application/Domain/Exceptions/`). They carry no behavior beyond extending
  `DomainException` / `InvalidArgumentException` and exposing the domain context their constructor receives. They are
  covered by the aggregate test that asserts the exception is thrown under the invariant-violating condition. The same
  test asserts the carried context when it matters. A dedicated test class for an exception is NEVER justified.
- **Command classes** (`src/Application/Commands/`). Data carriers with only a constructor. Nothing to test.
- **Record mappers** (`src/Driven/<Context>/Records/`, `src/Query/.../Database/Records/`). Covered through integration
  tests that exercise the full repository and query path, because the mapper is consumed by the adapter under test.

**Files directly under `src/` (root, no subdirectory)**. The `src/` root is reserved for application bootstrap and
infrastructure declarations: DI container registration, HTTP route bindings, framework settings, application kernel.
Examples: `src/Dependencies.php`, `src/Routes.php`, `src/DatabaseSettings.php`. They contain no business logic, and the
application bootstrapping itself validates them (the suite fails to load if any is broken). They MUST be excluded from
coverage via the `<exclude>` block in `phpunit.xml` and have no dedicated test class. Files inside subdirectories of
`src/` are NEVER eligible for this rule, regardless of their content. They are covered through the appropriate test path
defined elsewhere in this rule.

**Settings classes inside subdirectories of `src/`** (matching the `<Name>Settings.php` pattern from the Settings
contract. Settings at the root of `src/` are already covered by the rule above). Eligible for `<exclude>` when their
only logic is the `fromEnvironment()` factory plus optional methods that look up env vars by dynamically built keys.
Reading an env var and exposing the value is mechanical plumbing. A unit test would set the env, call the factory, and
assert the property. Pure tautology. Example in this service: `src/Driven/Payment/PaymentChargingSettings.php`. A
Settings class that adds calculation, transformation, validation, or cross-field dependencies leaves this exception and
is covered through its consumer's integration test.

**Domain services** (`src/Application/Domain/Services/`). Tested directly under
`tests/Unit/Application/Domain/Services/`. Domain services are stateless pure functions over domain objects with no I/O
dependencies, so they fit the unit-test path naturally. Each public method gets its own Given/When/Then scenarios
covering both happy paths and domain exceptions thrown when an input combination is invalid. This is the only
domain-layer building block that gets a dedicated unit test as the default rule (the closed set is owned by
`php-testing-unit`, aggregate roots also get dedicated tests, but other domain types are covered through their
aggregate). The structural contract is owned by the domain rules.

## When a dedicated test IS justified

The justification comment must state one of:

- "Behavior X cannot be reached through aggregate Y because it requires state Z that the aggregate refuses to enter."
  (Design smell. Prefer refactoring to expose the path through the root.)
- "This VO is consumed across N aggregates and has standalone parsing logic worth isolating."
- "This enum has a branching method `<method>()` whose truth table is larger than the cases exercised by any single
  aggregate."

Absence of the justification means the dedicated test is surplus and must be removed.

## Testing a domain exception through the raising behavior

Exception classes are pure invariant-violation signals owned by the domain. They are not the subject of behavior tests,
and the default stance already rules out a dedicated test class for one. A test constructs the conditions, invokes the
public method that is supposed to fail, and asserts the expected exception class is raised. Constructing the exception
directly and asserting on its accessors is prohibited: the exception's structure is exercised through the call path that
produces it. The `try`/`catch` form is reserved for accessors PHPUnit cannot reach (notably `getPrevious()` or a
value-carrying exception's domain accessors), as the general testing rules describe.

The prohibited shape (testing the exception as a value object, where no production code is exercised) versus the correct
shape (driving the call path that raises the exception) lives in `references/exception-raising-behavior.md`.

If no public method has a call path that raises the exception, the exception is dead code. Remove it instead of writing
a test against its constructor.
