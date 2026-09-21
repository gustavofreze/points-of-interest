---
name: php-bootstrapping-service
description: Orchestrate bootstrapping a whole PHP service by routing existing skills in phase order. Use when standing up a new service or bounded context, or building a service from the spec end to end.
---

# Bootstrap service

Routes the phases of standing up a service. Does not teach or apply rules directly: each phase delegates to the skill
that owns it, and that skill carries its own rules and gates. The role here is phase order, one spec read up front, and
the completeness gate at the close.

Target: the service or bounded context named in the request.

## When to use

- Bootstrap a new service or stand up a new bounded context from nothing.
- Build a service from the spec end to end (every use case, schema, and doc the spec assigns).

## When NOT to use

- One feature or endpoint in an existing service: `php-implementing-features`.
- Only the static skeleton or one config file: `php-creating-skeleton`.
- The question is only what the spec says: `php-reading-spec` and stop there.

## Stop condition

No spec found for the service through `php-reading-spec` (that skill owns where the spec lives and what to do when it is
not there): stop and ask. Do not invent a bounded context, its use cases, or its schema.

## Phase sequence

Each phase closes on its own skill's gate before the next begins.

1. Scope: `php-reading-spec`. Fix the bounded context and list every use case, read endpoint, table, and event the spec
   assigns to it. This list drives phases 3 to 5.
2. Skeleton: `php-creating-skeleton`.
3. Schema: `creating-migration`, one migration per schema change the spec defines.
4. Use cases, per use case in spec order: `php-implementing-features`.
5. Documentation: `php-writing-documentation`.
6. Final gate: `php-reviewing-conformance`.

## Completeness gate

- [ ] Every use case the spec assigns to the bounded context is implemented, or explicitly listed as deferred with a
      reason.
- [ ] Every phase closed on its own skill's gate, none skipped to check later.
- [ ] The conformance report from phase 6 carries no open finding.

## Does not do

- Does not restate what the routed skills already say.
- Does not widen scope beyond what the spec assigns to the bounded context.
