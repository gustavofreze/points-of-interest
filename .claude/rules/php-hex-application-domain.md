---
description: DDD building blocks of the domain layer. Ubiquitous language, aggregate roots and entities, state transitions, domain events, and domain exceptions.
paths:
    - "src/Application/Domain/Events/**/*.php"
    - "src/Application/Domain/Exceptions/**/*.php"
    - "src/Application/Domain/Models/**/*.php"
---

# Domain layer (DDD)

This rule keeps the domain layer's always-on structural invariants: the state-machine contract, the exception
layer-placement decision tree, and the 15-file context-grouping threshold. Every other concern is delegated to its
owner, listed at the end.

## Pre-output checklist

Verify before producing any file under `src/Application/Domain/`.

1. `Domain/Models/` threshold: when more than 15 files sit at root (excluding `Commons/`), group by aggregate context in
   the same change. See § Context-based grouping.
2. A failure class lands in the right directory: domain-invariant violations and value-object self-validation in
   `Domain/Exceptions/`, every other failure (not-found, conflict, idempotency, policy, outbound-port, use-case outcome)
   in `Application/Exceptions/`, configurable policy under `Application/Policy/`. Not-found is application, never
   domain. See § Exception layer placement.
3. A type under `Domain/` exists only when the domain itself consumes it. Never create a domain model, value object, or
   wrapper to serve another layer's convenience (commands carry primitives, adapters own their own DTOs).
4. A new transition follows § State machine contract: guard inside the mutator, silent re-application, domain refusal,
   one fact, void return.
5. A failure class is named for what failed, not by taste: `<Thing>FormatNotValid` for a format parse, `Invalid<Thing>`
   for a broken invariant, `Unsupported<Thing>` for an enum promotion. See § Exception naming.

## Context-based grouping

When `Domain/Models/` (excluding `Commons/`) holds more than 15 files at root, files MUST be grouped into context
subfolders named after the aggregate or bounded concept they belong to. This is a hard threshold enforced on every
change. Below it, keep the flat structure and do NOT pre-emptively group. At or above it, grouping is REQUIRED in the
same change that crosses it. Group by aggregate boundary, never by technical type (no `Enums/`, `ValueObjects/`, or
`Ids/`). The literal grouped-folder skeleton is owned by the php-architecture rule.

## State machine contract

Every state transition is a mutating method on the aggregate, and four premises hold for all of them:

1. The guard lives inside the mutator. Re-application of the transition's own resulting state returns silently
   (redelivery is a fact of life, the model absorbs it), an illegal origin state raises the state-machine domain
   exception, and no caller decides either case from outside the aggregate.
2. The mutator returns void and emits the fact of the transition as a domain event. One transition, one fact. No flow
   signal crosses the boundary.
3. An interaction with the outside world whose outcome is not yet known is modeled as an explicit state of the
   aggregate, named in business language. An unknown outcome is never labeled as a confirmed one: the aggregate stays in
   the in-flight state until a fact resolves it.
4. A transition that authorizes an external side effect is persisted before the side effect runs. The aggregate never
   performs the side effect itself: it records the authority, and the side effect belongs to a separate use case
   (`php-hex-application-handlers` § Facts drive the flow).
5. Only values cross into the aggregate: value objects, enums, identifiers, and primitives promoted at the boundary. No
   port, no service, no closure enters an aggregate method as an argument or lives as a property. The pure domain
   service is invoked by the handler with the loaded aggregates, never handed to the aggregate.

## Exception layer placement

This decision tree is the always-on residue: it decides which directory a failure class lives in. The exception class
shapes are in the php-creating-application skill.

`src/Application/Domain/Exceptions/` holds ONLY domain-invariant violations and value-object self-validation (the
format, membership, required-field, range, cross-field, and state-machine rules a value object or aggregate enforces on
itself). A domain exception is pure (no HTTP code, no formatted message) and extends `DomainException` (business
invariant violation, the default) or `InvalidArgumentException` (input validation at a factory or constructor boundary).
No other native parent is allowed in the domain. It MAY carry domain context typed with domain objects (value objects,
identifiers, enums), never primitives.

Every other failure is NOT a domain exception and lives in `src/Application/Exceptions/` (namespace
`<RootNamespace>\Application\Exceptions`): not-found (a lookup miss), conflict and idempotency, configurable policy
(allowlist, ceiling), provider or gateway and other outbound-port failures, and use-case outcomes (a routing miss, an
exhausted retry chain). Configurable policy classes that raise those live under `src/Application/Policy/`, never in the
domain. Broker errors, file system errors, database failures, and raw transport failures remain infrastructure and stay
out of both layers.

A not-found is an application failure, never a domain one: a lookup miss is not a domain invariant. It carries no
context (the lookup already holds the identifier it searched for), so it is an empty-body class thrown with no
arguments. Application exceptions extend `RuntimeException` (or `LogicException` only for true programmer errors), never
`DomainException`.

One carve-out applies to the primitive prohibition. An input-rejection exception, thrown when a raw input cannot be
promoted to a value object or enum, MAY carry the raw offending value as a primitive, because no value object exists yet
to carry it. The `Unsupported*` and `Invalid*` exceptions that hold a `public readonly string $value` are the conforming
shape under this carve-out.

## Exception naming

§ Exception layer placement decides which directory a failure class lives in. This section decides what it is called.
Three forms partition the domain exceptions by what actually failed, and a fourth covers the rest. The form is not a
style choice, it is what tells the reader which kind of failure they are looking at.

| What failed                                               | Form                    | Examples                                            |
|:----------------------------------------------------------|:------------------------|:----------------------------------------------------|
| A string fails promotion to a value object on format      | `<Thing>FormatNotValid` | `EmailFormatNotValid`, `UuidFormatNotValid`         |
| A domain invariant breaks (transition, range, arithmetic) | `Invalid<Thing>`        | `InvalidPaymentStatusTransition`, `InvalidPositive` |
| A raw value fails promotion to a closed-set enum          | `Unsupported<Thing>`    | `UnsupportedProvider`, `UnsupportedCountry`         |
| A business rule refuses a well-formed operation           | `<Thing><Condition>`    | `PaymentNotRefundable`, `InvitationExpired`         |

`Invalid*` and `*NotValid` are not two spellings of one idea, and they never compete for the same class. `Invalid*` says
an invariant broke, `*NotValid` says a string did not parse. A `< 1` check on an `int` is an invariant, so
`InvalidTrialDurationDays` and never `TrialDurationDaysNotValid`. A `preg_match` on a string is a format check, so
`ButtonIdFormatNotValid` and never `InvalidButtonIdFormat`.

The `Format` token is required in the first form. It is what separates a parse failure from an invariant violation when
the subject name alone is ambiguous.

**Renaming a mapped exception moves its HTTP error code.** The `code` defaults to the SCREAMING_SNAKE_CASE of the class
name, so a rename reaches `DriverExceptionMapping`, `openapi.yaml`, and the `docs/` page in the same change
(synchronization owned by web-documentation). The default is deliberately breakable: a code MAY diverge when the wire
term is a contract fact that differs from the internal name (`AccountNotFound` mapped to `USER_NOT_FOUND`), or when
several internal shapes collapse into one public bucket (`INVALID_REQUEST`). Divergence is a decision, never an
oversight.

## Delegated to owners

- Folder skeleton (`Models/`, `Events/`, `Exceptions/`, `Services/`): owned by the php-architecture rule.
- Business nomenclature and DDD building blocks: The shapes and how-to are in the php-applying-ddd skill.
- Aggregate root, entities, state transitions, domain events, typed identifiers and collections, and every code
  skeleton: The shapes and how-to are in the php-creating-application skill.
- Domain behavior, state transitions, and event semantics: Owned by the spec at `<spec-root>`, read it via the
  php-reading-spec skill. Not restated here. With `<spec-root>` unset there is no spec tier, and domain behavior that no
  rule states is asked for, never invented.
