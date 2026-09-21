---
description: Rules for thin write-side handlers in Application/Handlers/. Size and complexity limits, existence and uniqueness handling, the port and aggregate call paths, and prohibited patterns. The canonical shape bodies (A, B, C) live in the php-creating-application skill templates.
paths:
    - "src/Application/Handlers/**/*.php"
---

# Handlers

Handlers orchestrate one write use case as thin orchestration over the domain, translating a command into calls on the
domain and the outbound ports. The teaching that a handler contains no business rules (those live on aggregates and
domain services) is in the php-applying-hexagonal-architecture skill. This rule keeps the structural invariants: the
no-database-pre-check rule, the thin Shape A or B structural prohibitions, the one-trigger contract, and the
facts-drive-the-flow contract.

## Pre-output checklist

Verify before producing a handler.

1. The handler never pre-checks existence or uniqueness. The schema enforces the invariant and the repository translates
   the violation. No `Payments::exists(...)` style port. See § No database pre-check.
2. A Shape A or B body contains no `try/catch` for control flow, no nested `if` (one throwing null-guard aside), no
   loop, and no business calculation. When it needs any of these, escalate to Shape C. See § Thin handler structure.
3. The handler invokes no other handler. Shared write logic is a domain service or an aggregate method, never a second
   handler. See § No handler-to-handler calls.
4. The handler triggers the aggregate at most once per execution path, and never reads aggregate state to decide whether
   to call a transition. See § One aggregate trigger per handler.
5. The handler never starts the next use case: no flow message published after `save()`, no sequencing in the transport
   adapter, no outbound side effect whose authorizing transition was not committed first. The follow-up is dispatched
   from the published fact. See § Facts drive the flow.

## One aggregate trigger per handler

Four premises, each a hard invariant:

1. A handler triggers the aggregate at most once: one mutating call per execution path. The state guard lives inside
   that mutator, never before it. Guard-then-apply (reading aggregate state in the handler to decide whether to call a
   transition) is prohibited: it duplicates the state rule outside the domain and opens a check-then-act race.
2. The domain returns no flow signal. A transition returns void. A `bool` telling the handler what to do next moves a
   business decision out of the model.
3. Redelivery and out-of-order arrival are absorbed by the state machine itself (owned by `php-hex-application-domain` §
   State machine contract), never by handler branching.
4. When a use case seems to need a second aggregate trigger, the second trigger is a separate use case, started by the
   fact the first one published. See § Facts drive the flow.

## Facts drive the flow

A use case ends at its own commit. Follow-up work belongs to whoever consumes the published fact, never to the producer:

1. A handler never sequences another use case, and no driving adapter sequences two inbound ports for a single delivery.
2. A handler never announces its own outcome. The aggregate's fact, recorded in the same commit as the state change (the
   recording contract is owned by `php-hex-driven` § Outbox), is the only trigger the rest of the system sees.
   Publishing anything after `save()` is prohibited.
3. An external side effect (a provider submission, a void) runs only after the aggregate transition that authorizes it
   has been committed: authority first, side effect second, each in its own use case.
4. The map from fact to use case is declarative and lives in a single driving adapter, outside the hexagon. A fact with
   no mapped use case is acknowledged without effect.
5. Consumption is idempotent by aggregate state, never by delivery bookkeeping alone.

## No database pre-check

A handler never asks the database whether something exists, is unique, or is already taken before acting. The pre-check
has two problems, whether the question is positive (uniqueness) or negative (existence):

1. **Race condition.** Between the pre-check and to write, another request can change the answer. The handler thinks it
   is safe, the database disagrees.
2. **Domain leakage.** The handler ends up with a prohibited verb (`assert`, `ensure`, `verify`, `validate`).

The invariant is declared in the schema. The repository adapter catches the constraint violation and translates it into
the matching domain exception. The handler stays pure and just calls `save()`. This applies to every "exists, already
taken, already booked, not found, missing reference" invariant. The handler never asks, the database answers. A
`Payments::exists(...)` style port is prohibited.

CONTEXTUAL on the invariant being enforceable as a schema constraint. This no-pre-check rule presupposes that the
persistence layer translates a constraint (a unique index, a foreign key, a not-null) into the matching domain
exception. When an invariant cannot be expressed as a constraint, the handler still never queries the database to
pre-check: model the rule in the aggregate factory or a domain service and let the domain raise the exception.

## Thin handler structure

A handler matches one shape: A (single aggregate, create), B (single aggregate, state transition), or C (multi-aggregate
or compensating). A and B are the thin default. The literal `handle()` body for each shape is in the
php-creating-application skill.

The structural distinction below is the residue this rule owns. A Shape A or B body MUST NOT contain:

1. a `try/catch` used for control flow,
2. a nested `if` (a single throwing null-guard, `if (is_null($x)) throw <Aggregate>NotFound;`, is allowed in Shape B),
3. a `for`, `foreach`, or `while` loop,
4. a calculation or conditional on business state, a state-machine transition, or value-object construction that
   duplicates the aggregate factory.

When the use case genuinely needs branching, a `try/catch` fallback, or multi-aggregate coordination, escalate to Shape
C, with the orchestration directly in `handle()`. A cross-aggregate rule that has no input or output is a domain service
the Shape C handler invokes, never logic inlined in the handler.

## No handler-to-handler calls

A handler orchestrates one use case and never invokes another handler. Depending on a second handler couples two use
cases and opens a cycle, the exact hazard a general in-process command bus would hide (this service has none, by
design). Shared write logic that two use cases need is a domain service or an aggregate method, called by each handler
over its own ports, never one handler reaching through another.

## Delegated to owners

- The `handle()` body for Shape A, B, and C, the port and aggregate call-path parameter rules, and the prohibited,
  correct, and branching examples: The shapes and how-to are in the php-creating-application skill.
- The teaching that handlers carry no business rules and the application-as-orchestration framing: owned by the
  php-applying-hexagonal-architecture skill.
