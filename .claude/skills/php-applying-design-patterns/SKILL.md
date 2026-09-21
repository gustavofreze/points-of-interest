---
name: php-applying-design-patterns
description: Apply a Gang of Four design pattern (all 23). Use when choosing whether a pattern fits, or confirming its placement and naming in any layer. Triggers include Strategy, Adapter, Decorator, Observer, Builder.
---

# Design patterns (Gang of Four)

A reference for the 23 GoF patterns. A pattern is a named solution to a recurring design problem, a shape, never a class
name. This skill covers every pattern generically (intent, the problem it solves, the entry signal) and then how each
lands in this hexagonal PHP service. It is intent-triggered, consulted while CHOOSING a pattern, never injected into
every file.

This skill is not a mandate to use patterns. Most code needs none. Reach for one only when its entry signal appears,
then keep the class named for the business, not the pattern (owned and enforced by `php-code-style` § Design-pattern
names).

## When to use (and when NOT to)

| Use when                                                                        | Skip when                                                                     |
|---------------------------------------------------------------------------------|-------------------------------------------------------------------------------|
| A recurring design problem matches a known pattern's intent                     | A one-off with no recurrence, write the straight-line code                    |
| A growing conditional, a duplicated rule, or repeated wiring you want to remove | No entry signal yet, a pattern added preventively is speculative complexity   |
| Confirming the placement and naming of a pattern already present                | The language or the DI container already provides it (see § Already provided) |
| The variant arms have grown into full algorithms                                | A small `match` inside its owner still reads fine, leave it there             |

**Start simple. Reach for a pattern only when its signal appears, never preventively.**

## The 23 patterns

Intent and entry signal for each. The full structure, a generic example, the trade-off, and the this-service note are in
`references/catalog.md`.

### Creational (object construction)

| Pattern          | Intent                                       | Reach for it when                                                     |
|------------------|----------------------------------------------|-----------------------------------------------------------------------|
| Singleton        | One instance, global access                  | Almost never by hand, a container owns lifecycle                      |
| Factory Method   | Defer which type to instantiate to a method  | Construction has a meaningful name or branches by input               |
| Abstract Factory | Produce a family of related objects together | One call must return a matched set that cannot be wired independently |
| Builder          | Assemble a complex object step by step       | Many optional parts or a graph with sensible defaults (tests)         |
| Prototype        | Create by copying an existing instance       | Copy is cheaper than construction, almost never by hand               |

### Structural (composition)

| Pattern   | Intent                                            | Reach for it when                                                   |
|-----------|---------------------------------------------------|---------------------------------------------------------------------|
| Adapter   | Make an incompatible interface fit a port         | Wrapping an external API or library behind your own contract        |
| Bridge    | Split abstraction from implementation on two axes | Both an abstraction and its implementation vary independently       |
| Composite | Treat a part-whole tree uniformly                 | A genuine recursive hierarchy handled the same at every node        |
| Decorator | Add behavior by wrapping, same interface          | A cross-cutting concern (cache, retry, log) wraps without editing   |
| Facade    | One simple entry over a complex subsystem         | Hiding a multi-step or multi-object subsystem behind one call       |
| Flyweight | Share immutable instances to save memory          | A huge number of objects share intrinsic state                      |
| Proxy     | A stand-in controlling access to a target         | Access guarding, lazy loading, or a remote stand-in, same interface |

### Behavioral (responsibility and communication)

| Pattern                 | Intent                                                   | Reach for it when                                                  |
|-------------------------|----------------------------------------------------------|--------------------------------------------------------------------|
| Chain of Responsibility | Pass a request along handlers until one acts             | A pipeline where each stage may handle or pass on                  |
| Command                 | Turn a request into an object                            | Queue, log, undo, or decouple invoker from receiver                |
| Interpreter             | Evaluate sentences in a small language                   | A recurring grammar or expression to evaluate                      |
| Iterator                | Traverse a collection without exposing it                | Custom traversal, usually the language already gives it            |
| Mediator                | Centralize how a set of objects interact                 | Many-to-many object coupling you want to collapse                  |
| Memento                 | Capture and restore state without breaking encapsulation | Undo or snapshot of an object's internal state                     |
| Observer                | Notify dependents when state changes                     | One change must fan out to many reactions                          |
| State                   | Behavior changes with internal state                     | An object behaves differently across several states and operations |
| Strategy                | Interchangeable algorithm chosen at runtime              | A closed set of full-algorithm variants behind one interface       |
| Template Method         | Fix an algorithm skeleton, defer steps                   | A stable sequence with a few varying steps                         |
| Visitor                 | Add an operation to a structure without changing it      | A new operation over a stable type hierarchy, at a boundary        |

## Problem to pattern (quick index)

| The signal you feel                                             | Pattern                            |
|-----------------------------------------------------------------|------------------------------------|
| A big conditional choosing between full algorithms              | Strategy                           |
| A business rule (eligibility, validity) reused across use cases | Specification (a Strategy variant) |
| Behavior diverges per status across several operations          | State                              |
| A cross-cutting concern must wrap an existing component         | Decorator (or Proxy for access)    |
| An external API shape does not match my port                    | Adapter                            |
| A new operation must traverse a stable structure                | Visitor                            |
| One change must notify many reactions                           | Observer                           |
| A request must be queued, logged, or undone                     | Command                            |
| A complex object needs flexible step-by-step assembly           | Builder                            |
| A recursive part-whole tree handled uniformly                   | Composite                          |

## Already provided (do not hand-build in this service)

The language, the DI container, or a tiny-blocks facade already gives these. Reaching for them by hand is the
anti-pattern.

| Pattern   | Use instead                                                                      |
|-----------|----------------------------------------------------------------------------------|
| Singleton | The DI container owns instance lifecycle, constructor injection only             |
| Prototype | Immutable value objects copy by naming the class (`new Money(...)`), not `clone` |
| Iterator  | `IteratorAggregate`, generators, or the domain `Collection`                      |
| Bridge    | Ports and adapters already decouple abstraction from implementation              |
| Flyweight | Enum cases are shared instances by definition                                    |
| Facade    | The tiny-blocks facades (for example the `Http` facade) already wrap subsystems  |

## In this service (the overlay)

The patterns above are universal. Their placement and naming here are opinionated:

- Canonical homes (reuse, do not reinvent): Observer is domain events, Command is `Application/Commands/`, Chain of
  Responsibility is HTTP middleware, Factory Method is the named static factories on a type, Adapter is the Driver and
  Driven layers themselves, Decorator and Proxy live in `Driven/` over a port, Strategy, Specification, and State live
  in the domain (a domain service or a status enum).
- Naming: no pattern-name suffix, the class keeps its business name. Owned by `php-code-style`.
- Escalation thresholds (start simple): a status enum before per-state classes (State), a `match` moved into its owner
  before it is lifted to a Strategy, a combinator only on the third real composition (Specification). The detail is in
  `references/catalog.md`.

## Scaffold it

There is no generator for a pattern. A pattern is a shape, not a class to stamp out. To apply one, read its entry in
`references/catalog.md` first (intent, structure, a minimal example, the trade-off, and the this-service note), then
write the class in the canonical home that § In this service names for it. Keep the class named for the business, never
the pattern (owned by `php-code-style` § Design-pattern names). Reach for the pattern only when its entry signal
appears, never preventively.

## Catalog

`references/catalog.md` carries every pattern's intent, structure, a generic minimal example, the trade-off that bounds
it, and its this-service note. Read the catalog entry before writing the class.

## Sources

- Gamma, Helm, Johnson, Vlissides, "Design Patterns: Elements of Reusable Object-Oriented Software" (1994).
- Refactoring Guru, "Design Patterns" (https://refactoring.guru/design-patterns).
- The naming invariant and the dependency rules are this service's own, in `php-code-style` and `php-architecture`.
