---
description: Design-principle residue only, the precedence ladder, the binding force (MUST vs SHOULD), and the this-service homes for shared code. SOLID, DRY teaching, Tell-Don't-Ask, complexity, and decision routing moved to the php-applying-design-principles skill.
paths:
    - "src/**/*.php"
---

# Design principles (precedence, binding force, shared-code homes)

This rule keeps the precedence ladder, the binding force that gives the principles teeth, and the this-service homes for
shared code. The per-principle teaching (SOLID, DRY, Tell-Don't-Ask, complexity, decision routing) is in the
php-applying-design-principles skill. A textbook formulation never overrides an existing rule, and any disagreement is
flagged.

**Binding force.** The Pre-output checklist, Single responsibility, Open-closed, Liskov, Interface segregation,
Dependency inversion, Tell-Don't-Ask, DRY, and Precedence are MUST: a violation is a defect to fix before the change
lands. Complexity as a decision input and Decision routing are SHOULD: they steer the choice between admissible designs,
and a deviation ships only with its reason recorded.

## Pre-output checklist

1. Duplicated business knowledge has exactly one owner. Structural similarity alone is never unified. See § DRY.
2. Shared code lives only in the homes § DRY admits. Never a new technical-role folder. See § DRY.
3. When principles collide, § Precedence decides. Novel trade-offs are flagged, not resolved silently. See § Precedence.

## DRY

The DRY principle proper (knowledge over text, the wrong abstraction over duplication, the rule of three) is in the
php-applying-design-principles skill. Two this-service structural lists stay here, tied to the skeleton and CQRS:

1. **Admitted homes for shared code**, and no others: `Domain/Models/Commons/` and `Domain/Events/Commons/` (wrappers
   only), `Query/<Context>/Shared/` (read artifacts shared by slices of one context: the context read model, its
   projection, its row mapper, and context-specific masking), `Query/Shared/` (cross-context read side), the
   `Driver/Http` root for cross-cutting inbound infrastructure, and `Driven/Shared/` for the outbound infrastructure
   abstractions whose role IS the pattern (the connection, its statement and row types, the failure translation, and the
   log redactions the service adds to the structured logger), sanctioned by `php-code-style` § Naming exception 4. Each
   home is shared only inside its own layer, never across the hexagon:`Driven/Shared/` is consumed by the Driven
   adapters alone, never by `Driver/` (the dependency rule keeps every cross-layer coupling on `Application/Ports/`) and
   never by `Query/` (the read side owns its access, per `php-hex-query` § Read-write isolation). Creating a
   technical-role folder to host shared code is prohibited.
2. **Duplication that is correct by design**, never to be unified: models across bounded contexts (one context per
   service), read-side code relative to `Driven/` (CQRS), the double value-object construction in handlers, and the
   per-slice query scaffolding (the adapter, the keyset or offset query builder, and the `<Find...Request>` DTO). This
   protection covers the scaffolding that genuinely differs by granularity, not the read model, projection, or row
   mapper the slices of a context share: those have a single owner under `Query/<Context>/Shared/` per item 1.

## Precedence

When two principles point in opposite directions, the first applicable line wins:

1. Specification semantics over any rule (`CLAUDE.md` § Authority and conflict resolution).
2. An existing rule in `.claude/rules/` over a textbook principle.
3. Context boundary over DRY: never share to avoid duplicating across contexts.
4. Explicit duplication over a wrong abstraction.
5. Composition over inheritance.
6. No preventive abstraction: an interface, a helper, or a method exists only when a present consumer or a port boundary
   requires it. Methods are on demand, never preventive.

When no line above resolves the collision, the trade-off is novel: choose the option that keeps the change reversible
and adds no preventive abstraction, record the decision in the change summary, and proceed without blocking on it.

## Delegated to owners

- Single responsibility, Open-closed (composition form), Liskov (contract form), Interface segregation (port
  granularity), Tell-Don't-Ask, the general DRY principle, complexity as a decision input, and the decision-routing
  table: The shapes and how-to are in the php-applying-design-principles skill.
