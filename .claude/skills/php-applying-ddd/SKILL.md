---
name: php-applying-ddd
description: Domain-Driven Design reference, tactical and strategic. Use when deciding entity vs value object, drawing aggregate boundaries, placing a business rule, or integrating two bounded contexts.
---

# Domain-Driven Design (tactical and strategic)

This skill is an opinionated synthesis of Domain-Driven Design, not the canon itself. It distills the tactical building
blocks and the strategic patterns into the decisions you actually make while modeling, and it routes you to the source
of record for each question (Evans for the foundations, Vernon for aggregate design, Fowler for context boundaries).
Treat it as a map, not a mandate. DDD earns its complexity only on a domain with real rules. On a thin CRUD surface a
transaction script is the honest choice.

The concepts here are language-agnostic. The short examples use a neutral domain (Order, Customer, Account, Money,
Email, Document) so the shape carries over to any model. The ENFORCED specifics for this repository live in rules,
routed in § In this service.

## When to use (and when NOT to)

| Use When                                                        | Skip When                                                                          |
|-----------------------------------------------------------------|------------------------------------------------------------------------------------|
| The domain has real invariants and behavior, not just fields    | A thin CRUD form over one table with no rules, a transaction script is honest      |
| You must decide entity vs value object, or where a rule lives   | A read-only list or report (a CQRS read model needs no aggregate)                  |
| You are drawing aggregate boundaries and consistency rules      | A throwaway script, a one-off import, or a spike                                   |
| You are aligning names and code with the business vocabulary    | The whole feature is a pass-through to an external system with no model of its own |
| Two contexts must integrate and you need a translation boundary | The team has no shared language yet (settle the language first)                    |

**Start simple. Evolve complexity only when needed.**

## Tactical building blocks

The objects the domain is modeled with. Each answers one primary question. Treating one as another is the most common
modeling error, so the last column names the trap.

| Block            | Primary Question                       | Use It For                                                | Do Not Treat As                                    |
|------------------|----------------------------------------|-----------------------------------------------------------|----------------------------------------------------|
| Value Object     | What is it, by its value?              | Money, Email, a date range, a quantity, a status          | A row to persist on its own, or a thing with an id |
| Entity           | Which one is it, over time?            | Something with identity and a lifecycle (Order, Customer) | A bag of public setters, or a record               |
| Aggregate        | What changes together?                 | A consistency boundary around one root plus its parts     | A convenience graph of every related object        |
| Aggregate Root   | Who guards the invariants?             | The single entry point for changes inside the boundary    | A pass-through that exposes its internals          |
| Domain Event     | What happened, in the past?            | A fact other parts react to (OrderPlaced, AccountDebited) | A command, a DTO, or a wire message                |
| Repository       | Where do aggregates live?              | A collection illusion, one per aggregate root             | A per-table DAO, or a query bag for the read side  |
| Domain Service   | Whose rule is it, when no one owns it? | A rule spanning two aggregates with no natural host       | A home for orchestration or persistence            |
| Factory          | How is a valid one born?               | Encapsulating non-trivial construction of a valid object  | A place to skip invariants                         |
| Domain Exception | Which invariant was violated?          | A named failure in business language                      | A carrier of HTTP codes or formatted strings       |
| Invariant        | What must always be true?              | A rule enforced inside the boundary, every change         | A check sprinkled across handlers and controllers  |

Deep detail and a generic example per block are in `references/tactical.md`.

## Decision tree: Entity vs Value Object

```
Does the thing have a distinct identity that must be
tracked over time, independent of its attributes?
│
├─ NO ──► Are two instances with equal attributes fully
│         interchangeable (no lifecycle of their own)?
│         │
│         ├─ YES ──► VALUE OBJECT
│         │          immutable, compared by value,
│         │          self-validating in the constructor
│         │
│         └─ NO ───► reconsider. Most "no identity" things
│                    still resolve to a value object
│
└─ YES ─► Does it change state over its lifetime and need
          to be told apart from an equal-looking peer?
          │
          ├─ YES ──► ENTITY
          │          identity by id, mutated through methods
          │
          └─ NO ───► VALUE OBJECT after all
                     (equal value, no separate lifecycle)
```

## Decision tree: Aggregate boundaries

```
Start from one entity. For each related object, ask:

Must this object stay transactionally consistent with the
root on every change (a true invariant spans both)?
│
├─ YES ─► Is it meaningful only inside this root
│         (no independent lifecycle, never loaded alone)?
│         │
│         ├─ YES ──► SAME AGGREGATE
│         │          reached only through the root
│         │
│         └─ NO ───► its OWN AGGREGATE
│                    referenced by id, loaded separately
│
└─ NO ──► SEPARATE AGGREGATE
          reference by id, accept eventual consistency
          (a domain event, never a shared transaction)

Rule of thumb: keep aggregates small. One root plus the
objects that cannot be correct on their own.
```

## Decision tree: Where does this logic go?

```
Where does this business rule belong?
│
├─ Uses only one entity's own state?
│   └─► METHOD ON THAT ENTITY (or its aggregate root)
│
├─ Uses only the fields of one value (format, range,
│   arithmetic, comparison)?
│   └─► METHOD ON THE VALUE OBJECT
│
├─ Spans two or more aggregates, or has no honest home
│   on any single one?
│   └─► DOMAIN SERVICE (stateless, domain types in and out)
│
└─ Loads, saves, calls an external system, or sequences
    a use case end to end?
    └─► NOT THE DOMAIN. An application handler over a port.
```

## Generic structure

A neutral domain layout, organized by aggregate and concept, never by technical type. Placeholders in `{braces}` stand
for your own names.

```
Domain/
├── Models/
│   ├── Commons/                       # shared primitives wrapped from libraries
│   │   ├── {SharedValueObject}.php
│   │   └── {AggregateIdentity}.php
│   ├── {Context}/
│   │   ├── {Aggregate}.php            # the aggregate root, the one entry point
│   │   ├── {Aggregate}Id.php          # typed identifier
│   │   ├── {ChildEntity}.php          # reached only through the root
│   │   ├── {ValueObject}.php          # immutable, self-validating
│   │   └── {Status}.php               # enum, guards its own transitions
│   └── {OtherContext}/
│       └── {OtherAggregate}Id.php     # foreign context, referenced by id only
├── Events/
│   └── {Aggregate}{PastTenseFact}.php # final readonly, carries value objects and ids
├── Exceptions/
│   └── {InvariantViolated}.php        # named after the rule, business language
└── Services/
    └── {BusinessOperation}.php        # only when a rule fits no single aggregate
```

## Short examples (neutral domain)

A value object is immutable, equal by value, and self-validating: the worked shape is in `references/tactical.md` §
Value Object. The aggregate-root transition example (guard, mutate, emit, never a setter) and the cross-aggregate domain
service example are in `references/tactical.md` (§ Invariant and § Domain Service).

## Strategic design

Strategic DDD is about boundaries between models, not classes inside one. It decides where one model ends, where another
begins, and how the two talk.

| Concept               | Primary Question                  | Use It For                                                   | Do Not Treat As                                     |
|-----------------------|-----------------------------------|--------------------------------------------------------------|-----------------------------------------------------|
| Ubiquitous Language   | What do we call this, together?   | A shared vocabulary binding code, tests, and conversation    | Jargon the developers invented alone                |
| Bounded Context       | Where does this model apply?      | The boundary inside which one model and one language hold    | A synonym for a module or a microservice by default |
| Context Map           | How do contexts relate?           | Naming the relationship between two contexts (and its risks) | An afterthought drawn once and never revisited      |
| Anti-Corruption Layer | How do we stay clean at the edge? | Translating a foreign model into our own at the boundary     | A thin rename, it is a real translation             |
| Shared Kernel         | What do two contexts truly share? | A small, jointly-owned model shared by explicit agreement    | A dumping ground of common code                     |

Each term and the full set of context-map relationship patterns are in `references/strategic.md`.

## Anti-patterns

| Anti-pattern           | Problem                                                                           | Fix                                                                                     |
|------------------------|-----------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------|
| Anemic Domain Model    | Entities are data bags, all logic sits in services, the model carries no behavior | Move the rule onto the aggregate, the entity, or the value object that owns the data    |
| Repository per Entity  | A repository for every table, including child entities, leaks the boundary        | One repository per aggregate root. Children are reached through the root                |
| God Aggregate          | One aggregate pulls in every related object, transactions get huge and contend    | Split by true invariants. Reference other aggregates by id, accept eventual consistency |
| CRUD thinking          | Modeling create, read, update, delete instead of business intentions              | Name the intentions (place, cancel, confirm). Methods reveal behavior, not storage      |
| Leaking infrastructure | The domain imports a framework, an ORM, an HTTP client, or a DB type              | Keep the domain pure. Depend on ports, translate at the adapter boundary                |
| Primitive obsession    | Money, Email, and identifiers passed as raw int and string                        | Promote each to a value object that validates and carries its own behavior              |

## Implementation order

1. Settle the ubiquitous language with the domain experts. Names come first, code follows.
2. Draw the bounded context and its neighbors on a context map before writing a class.
3. Model value objects and enums, validating in the constructor.
4. Name the domain exceptions after the invariants they guard.
5. Build the aggregate root: factory, intention-revealing transitions, and event emission.
6. Add a domain service only when a rule spans aggregates and fits in none of them.
7. Define one repository port per aggregate root, in domain types.
8. Test behavior and invariants through the aggregate, never the accessors.

## In this service (the overlay)

The concepts above are universal. Their ENFORCED shape in this repository lives in rules, so this overlay points, it
does not restate. The rules are the single source of truth. Read the rule before you write.

- The aggregate root as the one entry point, the guard-mutate-emit transition, and past-tense domain events are owned by
  the `php-creating-application` skill. The `Models/Events/Exceptions/Services` folder layout is owned by the
  `php-architecture` rule. Named domain exception placement and the context-grouping threshold are owned by
  `php-hex-application-domain`.
- The domain service shape (stateless `final readonly`, domain-only signatures, the only allowed collaborator is another
  domain service, ubiquitous-language name, Strategy and Specification as domain services) is carried by the
  `php-creating-application` skill.
- Strategic DDD (one bounded context per service, the anti-corruption layer at the `Driven/` boundary, referencing
  another context by id, the inward dependency rule, CQRS) is owned by `php-architecture`.
- Naming (no pattern-name suffix, no generic technical verbs) is owned by `php-code-style` (§ Design-pattern names, §
  Naming).

## Scaffold it

To generate the artifact (an aggregate, value object, enum, domain event, domain exception, or domain service) rather
than hand-roll it, run the `php-creating-application` skill. It carries the canonical templates and the completeness
gate, and it loads the rules above in priority order. For the strategic boundary work that anchors a new aggregate, read
the spec first with `php-reading-spec`.

## Reference documentation

| Reference               | Read it for                                                                                                                                                                                |
|-------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| references/tactical.md  | Entity, value object, aggregate and root, domain event, repository, domain service, factory, domain exception, and invariant, each with a generic example, plus guarding versus validating |
| references/strategic.md | Ubiquitous language, bounded context, context map and its relationship patterns, anti-corruption layer, and shared kernel                                                                  |

## Sources

- Eric Evans, "Domain-Driven Design: Tackling Complexity in the Heart of Software" (Addison-Wesley, 2003), the Blue
  Book. Distilled in the free DDD Reference (https://www.domainlanguage.com/ddd/reference/).
- Vaughn Vernon, "Implementing Domain-Driven Design" (Addison-Wesley, 2013), the Red Book.
- Vaughn Vernon, "Effective Aggregate Design" (2011), the three-part essay
  (https://www.dddcommunity.org/library/vernon_2011/).
- Martin Fowler, "BoundedContext" (https://martinfowler.com/bliki/BoundedContext.html).
- Martin Fowler, "AnemicDomainModel" (https://martinfowler.com/bliki/AnemicDomainModel.html).
- The enforced specifics are this service's own, in the rules named under § In this service.
