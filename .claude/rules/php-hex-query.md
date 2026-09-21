---
description: Read side (CQRS). The structural residue only, read code lives at the narrowest scope containing all its consumers, and read-write isolation. The slice shapes, read-pipeline templates, and pagination shapes live in the php-creating-query skill.
paths:
    - "src/Query/**/*.php"
---

# Query (read side)

The `Query/` layer is the segregated read side. Each read use case is a self-contained vertical slice. This rule keeps
three structural invariants: placement by consumer scope, the read-pipeline boundary, and read-write isolation.

## Pre-output checklist

1. Read code lives at the narrowest scope containing all its consumers: one use case in `<Context>/<UseCase>/`, two or
   more slices of one context in `<Context>/Shared/`, two or more contexts in `Query/Shared/`. Nothing cross-cutting
   lives at the root of `Query/`. See § Structure.
2. The adapter returns a single read model or a `Page` of serialized views, never a raw DBAL row. The endpoint only
   serializes. See § The read pipeline.
3. Nothing from `Query/` is reused in `Application`, `Driver`, or `Driven`, and no write-side repository is reused for
   reads. See § Read-write isolation.

## Structure

The `Query/` layer subdivides by bounded context first, exactly like every other layer. Inside a context, code lives at
the narrowest scope that contains all of its consumers. Three homes:

1. **`<Context>/<UseCase>/`** holds only the artifacts a single read use case does not share (the `<Finding>.php` port,
   the inbound HTTP adapter under `Http/`, the outbound database adapter and its per-slice query builder under
   `Database/`).
2. **`<Context>/Shared/`** holds the read artifacts shared by two or more slices of the same context (the context read
   model, its projection SQL, its row mapper, and any context-specific masking). They move here the moment a second
   slice needs them, never copied per slice.
3. **`Shared/`** holds only genuinely cross-context read-side infrastructure (the cross-context HTTP exception mapping,
   shared request validation, the generic query helpers, the generic masking primitive, and the generic read-model
   DTOs).

Placement follows consumer scope. One use case to `<Context>/<UseCase>/`, two or more slices of one context to
`<Context>/Shared/`, two or more contexts (a single cross-context consumer counts) to `Query/Shared/`. Cross-cutting
query infrastructure NEVER lives at the root of `Query/`.

The per-home artifact layout, the keyset and offset pagination slice shapes, and the read-pipeline templates are in the
php-creating-query skill. The HTTP wire envelope is owned by the spec at `<spec-root>`, read it via the php-reading-spec
skill, and `php-hex-driver-http` § Pagination carries the default that holds with `<spec-root>` unset and that the spec
overrides where it speaks. The per-slice query parameter shapes are in the php-creating-query skill.

## The read pipeline

The adapter never returns a raw row. It returns a single read model (find-by-id) or a `Page` whose items are the
serialized views, mapping rows inside the adapter. The endpoint only serializes: it calls `toArray()` on the single read
model, or `toResponse()` on the page. A `->map()` that turns rows into views at the endpoint is the boundary leak this
rule forbids. The read model is strongly typed (scalars plus typed sub-DTOs for fixed-shape nested data), and a raw
associative row never escapes the adapter.

The literal read-model, mapper, and masking shapes are in the php-creating-query skill.

## Read-write isolation

1. Each query use case's adapter and per-slice query builder live within `Query/<Context>/<UseCase>/` and never reside
   in `Driven/`. The slice composes its context read model, projection, and row mapper from `Query/<Context>/Shared/`,
   and never imports from a sibling slice.
2. Never reuse a write-side repository for reads. Even when `Driven/` contains a similar query, the read side remains
   fully independent.
3. Nothing from `Query/` is reused in `Application`, `Driver`, or `Driven`. Read models, responses, and adapters are
   isolated to respect CQRS segregation.
4. The read side maps its own exceptions to HTTP through its own `Query/Shared/Http` `ExceptionMapping`, never the
   write-side `Driver/Http` mapping.
