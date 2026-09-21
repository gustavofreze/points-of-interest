---
description: Hexagonal architecture structural residue only, the inward dependency rule, bounded-context subdirectory naming, CQRS, the infrastructure-files exception, and one bounded context per service. Folder skeleton, layer shapes, and strategic DDD moved to their owners.
paths:
    - "src/**/*.php"
---

# Hexagonal architecture (ports & adapters)

This rule keeps only the always-on structural residue of the hexagon: the inward dependency rule, the bounded-context
subdirectory-naming invariant, the CQRS one-liner, the infrastructure-files exception, the Settings contract, and the
one-bounded-context-per-service fact. The folder skeleton, the per-layer shapes, and strategic DDD how-to are delegated
to their owners, listed at the end.

## Pre-output checklist

Verify every item before producing or relocating any file under `src/`.

1. Dependencies point inward only: `Driver` to `Application` to `Domain`, and `Driven` implements `Application/Ports/`.
   See § Dependency rule.
2. Subdirectory names under the layers represent **bounded contexts**, never visibility, audience, technical role, or
   layering. See § Subdirectory naming.
3. CQRS holds: each query use case is self-contained, and a write-side repository is never reused for reads. See § CQRS.
4. The `src/` root holds only `Routes.php`, `Dependencies.php`, and app-wide `<Name>Settings.php`, a closed list. See §
   Infrastructure files exception.
5. One bounded context per service, translated at the `Driven/` boundary. See § Bounded context.
6. `<Name>Settings` reads the environment via `EnvironmentVariable::from` only and never throws an exception to its own.
   See § Settings.
7. `Dependencies.php` is declarative wiring only: no validation logic and no throwing guard. See § Composition root.

## Dependency rule

Dependencies always point inward:

1. `Driver` to `Application` to `Domain`.
2. `Driven` implements `Ports` (interfaces defined in `Application/Ports/`).
3. `Domain` has zero knowledge of `Driver`, `Driven`, `Query`, or framework code. External libraries are restricted to a
   small whitelist and accessed only through wrappers.

All coupling between layers is interface-based injection through `Application/Ports/`. No `new ConcreteClass()` inside
domain or application code.

## Subdirectory naming

Subdirectory names under `Application/Domain/Models/`, `Application/Ports/`, `Driven/`, `Driver/Http/Endpoints/`, and
`Query/` represent **bounded contexts in the business domain** (`Payment`, `Organization`, `Charge`), nouns from the
ubiquitous language. `Application/Commands/` and `Application/Handlers/` MAY group their files under the same
per-context subfolders when a service spans more than one context, and a flat layout is equally valid. The `Command`
marker stays at the `Commands/` root regardless.

They never represent:

1. **Visibility** (`Internal`, `External`, `Public`, `Private`): a routing concern, expressed in the route prefix
   (`/internal/...`) or in a middleware that authorizes the caller.
2. **Audience** (`Admin`, `Backoffice`, `Mobile`, `Web`): a routing concern. The same `Payment` context serves whoever
   the route exposes it to.
3. **Technical role** (`Sync`, `Async`, `Batch`, `Realtime`): a deployment concern.
4. **Layering** (`Service`, `Manager`, `Handler` as a context name): technical labels, not business concepts.

When a query or endpoint is restricted to a specific consumer, the restriction lives in the route, the OpenAPI tag, the
middleware chain, or the documentation. Never in the folder name.

## CQRS

Commands mutate state and return void or a typed result. Queries are read-only and return typed read models. The read
side is fully segregated: a write-side repository is never reused for reads, and nothing from `Query/` is reused
elsewhere in the hexagon. The full read-write isolation contract is owned by the `php-hex-query` rule.

## Infrastructure files exception

The `src/` root (no subdirectory) admits exactly three kinds of files, a closed list: `Routes.php` (the HTTP route
bindings), `Dependencies.php` (the DI composition root), and app-wide `<Name>Settings.php` value objects
(`AppSettings.php`, `DatabaseSettings.php`). No other class is created at the `src/` root. These files are exempt from
the "no private methods" and class-decomposition rules.

Eligibility for `<exclude>` in `phpunit.xml` is a separate and equally closed list: these root files, plus every
`<Name>Settings.php` wherever it lives (§ Settings puts provider and adapter Settings beside their adapter, so the
exclude list is not a root-only list). A Settings class is env-only and carries no behavior to test, and the composition
root is declarative wiring. Every other class earns its coverage.

This carve-out is referenced by the php-code-style and php-skeleton-tooling rules.

## Composition root

`src/Dependencies.php` is declarative wiring only: it resolves collaborators, builds the object graph, and binds
concrete adapters to ports. It carries no conditional business or validation logic and no throwing guard. A precondition
belongs to the concrete class it protects, never to the factory closure that wires it: the composition root is
coverage-excluded, so logic placed there can never be tested or mutation-killed. The literal `definitions()` shape (one
method per layer, composed by spread) is in the php-creating-skeleton skill (`references/dependencies.md`).

## Settings

`<Name>Settings` is a `final readonly` value object with promoted public values and a `fromEnvironment()` static
factory. App-wide Settings live at the `src/` root, provider and adapter Settings live beside their adapter. Three
invariants:

1. Every environment read uses `EnvironmentVariable::from`, never `EnvironmentVariable::fromOrDefault` and never a
   code-side fallback. A missing or renamed variable fails at startup, so the app only ever runs on valid, current
   configuration.
2. A Settings class declares no exception and throws nothing of its own. The library's missing-variable failure is the
   only guard. Validation beyond presence (scheme, format, range) lives in the concrete implementation that consumes the
   value (a client, an adapter, a value object), never in Settings and never at the composition root (declarative wiring
   only).
3. No methods, except the dynamic env-key lookup variant. The literal shapes are in the php-creating-skeleton skill
   (`references/settings.md`, `references/settings-dynamic-key.md`).

## Bounded context

One bounded context per service. Each microservice owns exactly one bounded context, its domain model is the authority
for that context, and it never references another service's domain objects directly. Integration crosses process
boundaries through commands, queries, or integration events, carried over HTTP or a message broker
(`<messaging-transport>`). With `<messaging-transport>` unset the service integrates over HTTP alone and the boundary
rule is unchanged. Foreign concepts are translated to this service's ubiquitous language at the `Driven/` boundary (the
anti-corruption layer).

## Delegated to owners

- Folder skeleton (the canonical `src/` and `tests/` tree): The shapes and how-to are in the php-creating-skeleton
  skill.
- Routes (`src/Routes.php`), Dependencies (`src/Dependencies.php`, the composition root), and the `<Name>Settings` value
  objects with their `fromEnvironment()` factory and `Settings` suffix: The shapes and how-to are in the
  php-creating-skeleton skill.
- Driven adapter structure, the `Driver/Http/` folder layout, and the read-side Query structure (including pagination
  choice): The shapes and how-to are in the php-creating-driven, php-creating-driver, and php-creating-query skills.
- Outbound port parameter types, write-side handler shapes, and the persistence (Doctrine DBAL) contract: The shapes and
  how-to are in the php-creating-application and php-creating-driven skills.
- Strategic DDD beyond the one-context fact (context map, ubiquitous-language modeling): The shapes and how-to are in
  the php-applying-ddd skill, and domain behavior is owned by the spec at `<spec-root>`, read it via the
  php-reading-spec skill. Not restated here. With `<spec-root>` unset there is no spec tier, and domain behavior that no
  rule states is asked for, never invented.
- SOLID, DRY, principle precedence, and pattern selection: owned by the php-design-principles rule and the
  php-applying-design-principles skill.
