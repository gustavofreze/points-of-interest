---
name: php-applying-hexagonal-architecture
description: Ports and adapters (hexagonal) reference. Use when deciding which layer code belongs to, whether something is a port or an adapter, or which side (inbound or outbound) it sits on.
---

# Hexagonal architecture (ports and adapters)

This skill is an opinionated synthesis of the ports and adapters pattern, distilled for a PHP service. The architecture
is universal and language-agnostic, so the concepts and the short PHP 8 examples here use a domain-neutral vocabulary
(Order, Customer, Account, Money, Email, Document). For the canonical answer to any single question, follow the primary
source named in § Sources (Cockburn for the shape, Martin for the dependency direction). This is a teaching reference,
not dogma, and not a restatement of the enforced rules. Where a specific is enforced in this repository, the § In this
service overlay routes you to the rule that owns it.

## When to use (and when NOT to)

| Use when                                                     | Skip when                                                            |
|--------------------------------------------------------------|----------------------------------------------------------------------|
| Deciding which layer a class belongs to                      | The placement is obvious and already covered by a layer rule         |
| Cannot tell a port from an adapter, or inbound from outbound | Only a literal file shape is needed (run the scaffold skill instead) |
| A dependency seems to point the wrong way (outward)          | A tiny script with no business rule and no swappable I/O             |
| A technology needs to be swappable behind a contract         | The "technology" is the language itself, not an external dependency  |
| Reviewing whether the core stays free of infrastructure      | The change touches only one adapter's internals, behind its port     |

**Start simple. Evolve complexity only when needed.** A port exists when a real consumer or a real boundary requires it,
never preventively.

## The dependency rule (and the runnable-core test)

Source-code dependencies point inward only, and the core must run with every adapter replaced by a double (no UI, no
database). The full rule, the runnable-core test, and the inversion mechanism, each with a PHP example:
`references/ports-and-adapters.md` § The dependency rule and § The runnable-core test.

## Concept boundaries

| Block                   | Primary question                               | Use it for                                                             | Do not treat as                                        |
|-------------------------|------------------------------------------------|------------------------------------------------------------------------|--------------------------------------------------------|
| Domain                  | What is always true, regardless of technology? | Entities, value objects, aggregates, domain services, domain events    | A place for SQL, HTTP, framework, or vendor types      |
| Application (use cases) | What does the system do, step by step?         | Handlers orchestrating one use case, the ports they depend on          | A home for business rules (those belong to the domain) |
| Inbound port            | How does the outside ask the core to act?      | The entry contract a driver calls to drive one use case                | A controller, a request object, or a transport type    |
| Outbound port           | What does the core need from the outside?      | The capability contract an adapter implements                          | A database, an HTTP client, or a leaked vendor type    |
| Driver adapter          | How does a real transport reach the core?      | Controllers, endpoints, webhooks, CLI commands, consumers              | The place to make a domain decision                    |
| Driven adapter          | How is a need actually fulfilled?              | Repositories, gateways, publishers, clocks, mailers                    | A concrete type the core imports directly              |
| Composition root        | Where are the wires soldered?                  | The DI container building the object graph, choosing concrete adapters | A home for business logic or per-request decisions     |

## Decision trees

### Where does this code go (domain vs application vs adapter)?

```
Does the code express a business rule or an invariant?
├─ YES → Does it touch I/O (DB, HTTP, queue, clock, env, framework)?
│        ├─ NO  → DOMAIN (entity, value object, aggregate, domain service, event)
│        └─ YES → It is not pure domain. Split it:
│                 the rule  → DOMAIN
│                 the I/O   → ADAPTER, behind an outbound port
└─ NO  → Does it orchestrate a use case (load, call the domain, persist)?
         ├─ YES → APPLICATION (a handler behind an inbound port)
         └─ NO  → Does it speak a concrete technology (HTTP, SQL, broker, env)?
                  ├─ YES → ADAPTER (Driver if inbound, Driven if outbound)
                  └─ NO  → It is a contract. A PORT (an interface in the core)
```

### Is this a port or an adapter?

```
Is it an interface the core owns and the core depends on?
├─ YES → PORT (lives inside the application core)
│        It names a capability in business terms, with no transport words.
└─ NO  → Is it a concrete class that speaks one specific technology?
         ├─ YES → ADAPTER (lives outside the core)
         └─ NO  → Neither. A pure rule is DOMAIN, a use case is a HANDLER.
```

### Is this port inbound or outbound?

```
Which way does control cross this port?
├─ The outside DRIVES the core through it
│   (a request enters: HTTP endpoint, CLI, webhook, message consumer)
│   └─ INBOUND port (driving, primary)
│      Implemented BY the core (a handler implements it).
└─ The core DRIVES the outside through it
    (the core needs something: persistence, a provider, a broker, the clock)
    └─ OUTBOUND port (driven, secondary)
       Implemented OUTSIDE the core (an adapter implements it).
```

### Is this adapter a driver or a driven?

```
Which side of the port does the adapter sit on?
├─ It RECEIVES input and then calls an inbound port
│   (controller, endpoint, webhook, consumer, CLI command)
│   └─ DRIVER adapter (primary, the inbound side)
│      Depends on an inbound port. Never on the core internals.
└─ It IMPLEMENTS an outbound port and speaks a technology
    (repository, gateway / API client, publisher, clock, mailer)
    └─ DRIVEN adapter (secondary, the outbound side)
       Implements an Application outbound port. Owns the transport.
```

## Generic structure

A neutral shape with `{placeholders}`. The concrete folder skeleton for this repository is owned by the
php-creating-skeleton skill, so treat the tree below as the idea, not the literal layout.

```
{core}/                            # the application core (zero outward dependencies)
├── domain/                        # entities, value objects, aggregates, services, events
│   └── {Aggregate}/
├── ports/
│   ├── inbound/                   # driving contracts: {UseCase}ing interfaces
│   └── outbound/                  # driven contracts: {Aggregate}s, {Provider}Gateway
├── {UseCase}Command               # the input to one use case
└── {UseCase}Handler               # implements an inbound port, orchestrates the use case
driver/                            # primary / driving adapters (call inbound ports)
└── {transport}/                   # Http controllers, CLI, webhooks, consumers
driven/                            # secondary / driven adapters (implement outbound ports)
└── {technology}/                  # {Aggregate}Repository, {Provider}Gateway, {Topic}Publisher
{CompositionRoot}                  # wires concrete adapters to ports (the DI container)
```

The port shapes (inbound and outbound), the handler that implements the inbound port, the driver and driven adapters
around them, and the composition root that wires them are in `references/ports-and-adapters.md`.

## Building blocks

| Building block            | Role                                                | Lives in                  |
|---------------------------|-----------------------------------------------------|---------------------------|
| Entity / Aggregate        | Identity plus the invariants of its boundary        | Domain (core)             |
| Value object              | An immutable value and the rules that bind it       | Domain (core)             |
| Domain service            | One rule that spans aggregates, with no I/O         | Domain (core)             |
| Domain event              | A fact the domain emits when something happens      | Domain (core)             |
| Inbound port (interface)  | The write-side entry contract for one use case      | Application (core)        |
| Command                   | The typed input to one use case                     | Application (core)        |
| Handler                   | Orchestrates one use case over the domain and ports | Application (core)        |
| Outbound port (interface) | A capability the core needs, in domain terms        | Application (core)        |
| Driver adapter            | Receives a transport, calls an inbound port         | Driver (outside, driving) |
| Driven adapter            | Implements an outbound port, owns the transport     | Driven (outside, driven)  |
| Composition root          | Wires concrete adapters to the ports                | Bootstrap (outside)       |

## Anti-patterns

| Anti-pattern                                                                                             | Problem                                                                                                         | Fix                                                                                                           |
|----------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------|
| Domain imports infrastructure (a DB client, an HTTP SDK, a framework type)                               | The core can no longer run without that technology, the dependency rule is broken, the runnable-core test fails | Declare an outbound port in the core, move the technology into a driven adapter behind it                     |
| A controller calls a repository directly                                                                 | The use case has no home, orchestration and transport tangle, the core cannot run without HTTP                  | Route the controller through an inbound port to a handler, let the handler call the outbound port             |
| A concrete adapter type crosses the boundary (a handler type-hints `SqlOrders`)                          | The core is welded to one adapter, swapping it means editing the core                                           | Depend on the port interface, let the composition root inject the concrete adapter                            |
| A port exposes infrastructure types (a method takes a `Connection`, returns a raw row or a PSR response) | The technology leaks through the contract, the inversion is only cosmetic                                       | Express the port in domain types (value objects, aggregates, DTOs), serialize inside the adapter              |
| An adapter holds a business rule                                                                         | The rule hides outside the domain, two adapters drift, the knowledge has no single owner                        | Move the rule onto the owning aggregate, value object, or domain service, leave the adapter to translate only |
| Anemic core, behavior scattered in handlers and adapters                                                 | The "domain" is data bags, real logic lives in the outer layers                                                 | Push behavior inward, tell the aggregate to act rather than asking for its state                              |
| The driver calls the handler class instead of the inbound port                                           | The driver couples to one concrete implementation, not a contract                                               | Depend on the inbound port interface, inject the handler through the composition root                         |

## Implementation order

1. Model the domain first: entities, value objects, aggregates, domain services. No I/O at all.
2. Name the use case as an inbound port (the write-side contract) and define its command.
3. Discover the outbound ports the use case needs (persistence, providers, the clock), and declare them in the core in
   domain terms.
4. Write the handler that implements the inbound port and orchestrates over the domain and the outbound ports.
5. Implement the driven adapters behind the outbound ports (repository, gateway, publisher), with the anti-corruption
   translation at the boundary.
6. Implement the driver adapter that builds the command from transport input and calls the inbound port.
7. Wire everything at the composition root, where the DI container picks the concrete adapters.
8. Verify the dependency rule: the core compiles and its use cases run with no outer-layer import and no real database.

## In this service (the overlay)

The concepts above are universal. Their enforced specifics in this repository live in rules (the single source of
truth). This overlay only routes you to them, it does not restate their content.

- **The dependency rule, the four-layer contract, CQRS, the composition root, and the anti-corruption boundary** are
  owned by `php-architecture`. Read it before creating any file under `src/`.
- **Inbound ports** (the `-ing` write-side entry contract, the single `handle` method, the one handler per port) are
  carried by the `php-creating-application` skill.
- **Outbound ports** (hexagon-only parameter types, nullable reads with caller-side not-found, no infrastructure
  leakage, repository and gateway naming) are carried by the `php-creating-application` skill.
- **Driven (outbound, secondary) adapters** (adapter-kind naming, outbox invariants) are owned by `php-hex-driven`. The
  driver-versus-driven criterion is owned by the `php-creating-driven` and `php-creating-driver` skills.
- **Driver (inbound, primary) HTTP adapters** (the `Driver/Http` layout, the inward-only import rule, the error stack)
  are owned by `php-hex-driver-http`.
- **The Dependency Inversion Principle** as operationalized here (concrete outer-layer types never cross inward) is
  owned by the `php-design-principles` rule.

## Scaffold it

Once you know where the code goes, generate the artifact with the matching builder skill:

- The application core (domain artifacts plus the write use case: command, inbound port, handler, outbound port):
  `php-creating-application`.
- A driver (inbound, primary) HTTP adapter (endpoint, controller, webhook, Request DTO): `php-creating-driver`.
- A driven (outbound, secondary) adapter (repository, gateway, client, publisher): `php-creating-driven`.

## Reference documentation

| Topic                                                                                                                                                               | File                               |
|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| The dependency rule, the runnable-core test, the port and adapter taxonomy, the composition root, and the anti-corruption boundary, each with a neutral PHP example | `references/ports-and-adapters.md` |

## Sources

- Cockburn, Alistair. "Hexagonal Architecture" (2005). https://alistair.cockburn.us/hexagonal-architecture/
- Martin, Robert C. "The Clean Architecture" (2012).
  https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html
- Martin, Robert C. "Clean Architecture: A Craftsman's Guide to Software Structure and Design", Prentice Hall (2017).
- Martin, Robert C. "The Dependency Inversion Principle", C++ Report (1996). The inversion that makes the outbound port
  point inward.
