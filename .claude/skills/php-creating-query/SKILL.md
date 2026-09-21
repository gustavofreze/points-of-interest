---
name: php-creating-query
description: Build the read-side slice (Finding, Endpoint, Request, database adapter, Queries, Response) and own the read error stack. Use when creating a read endpoint, list, find, query, or read-side adapter.
---

# Add query

Builds the read-side slice. Routes to the query rules, and carries the canonical read-side shapes and the collation
how-to that used to live inside the rules.

Target: the query named in the request (e.g. `FindPaymentsByOrganization`).

Build the thinnest read that satisfies the contract. Start from a single-table SELECT, and add a JOIN, a JSON
projection, or a masker only when the contract demands it. Match the pager to the collection (offset for a bounded
reference set, keyset for an unbounded one).

## When to use

- Create a read endpoint, a list, a find, or a read-side adapter under `src/Query`.
- Add or extend the read error stack (the `Query/Shared/Http` `ExceptionMapping`).

## When NOT to use

- The write side (Command, Handler): use `php-creating-application`.
- A full slice with writes: use `php-implementing-features`.
- A write HTTP endpoint, route, or webhook: use `php-creating-driver`.
- Persist an aggregate or add a Repository (the Driven side): use `php-creating-driven`.
- Change schema or add a migration: use `creating-migration`.

## Rules applied

Load and follow, in this priority:

- `php-hex-query`: slice shape (Finding, Endpoint, Request, Database, Response, Mapper) and the read error stack (the
  read `ExceptionMapping`).
- `php-hex-sql-queries`: bind parameters (no `sprintf` or interpolation), and utf8mb4.
- the `php-writing-documentation` skill: the read endpoint enters the docs page and `openapi.yaml`. `web-documentation`
  owns the cross-file sync and prose style.
- `php-testing-unit`: test of the endpoint adapter. `php-testing-integration`: test of the database adapter.

## Shapes and how-to (read when scaffolding)

The literal read-side shapes and the collation how-to that the rules used to inline live here, surfaced only when this
skill runs:

- For GoF pattern selection, use the `php-applying-design-patterns` skill (cross-cutting, all families). The naming
  invariant is in `php-code-style` (§ Design-pattern names).
- `references/keyset-query-slice.md`: the keyset slice layout (the `<Resource>Finding` port, the
  `<Resource>FindingAdapter`, the `<Resource>KeysetQuery`, and the `<Resource>Scope`). Worked from this service's
  `FindByOrganization` slice, with the scope left neutral in the sample and the concrete names in a trailing note.
  `SeekClause`/`SortClause`/`Filters` are library types, the only per-slice query classes are `<Resource>KeysetQuery`
  and `<Resource>Scope`.
- `references/offset-query-slice.md`: the offset slice for a bounded reference collection (the `<Resource>OffsetQuery`
  plus the slice-specific `COUNT` `Queries` class).
- `references/read-model.md`: the strongly-typed read model plus the generic `Money` and `Reference` sub-DTOs, with
  `toArray()` as the single owner of the wire envelope.
- `references/mapper.md`: the `<Resource>Mapper` Data Mapper that coerces the raw row into the read model.
- `references/masking.md`: the context-specific masker (`<Resource>Mask`, dynamic-shape output) plus the generic `Mask`
  primitive.
- `references/select.md`: the read SQL shapes (SELECT single table, SELECT with JOIN, multi-column ORDER BY, and the
  JSON-extraction projection).
- `references/collation-in-comparisons.md`: the MySQL collation decision tree (the real-column case, the bind-parameter
  case, the pin-versus-domain-invariant choice, and the MySQL 8.4 verification). The connection-charset invariant itself
  stays in `php-hex-sql-queries`.

## Assembly order

1. Read the spec if the query carries business rule (`php-reading-spec`).
2. Finding (read port) and Response (collection/item) with the Mapper. Start the read model, mapper, and masking from
   `references/read-model.md`, `references/mapper.md`, and `references/masking.md`.
3. Database adapter and the per-slice query builder, with the SQL per the sql-queries rules. Start from
   `references/keyset-query-slice.md` (unbounded) or `references/offset-query-slice.md` (bounded), and the SQL from
   `references/select.md`.
4. Read endpoint and Request, with input validation.
5. Read error stack (the `Query/Shared/Http` `ExceptionMapping`), if new. Add the slice's exception arm to the read
   stack per `php-hex-query` § Read-write isolation.
6. Tests: unit of the endpoint and integration of the adapter, via `php-writing-tests`.

## Completeness gate

- [ ] Finding named per the query rules, without leaking the persistence type.
- [ ] Every read over a tenant-scoped table is filtered by that table's tenant discriminator (the multi-tenant isolation
      invariant, not an optional filter). Look at the table you are querying, it either carries a tenant scope column or
      it does not, and where the schema alone does not settle which tables are tenant-scoped, the spec at `<spec-root>`
      does, read it via the `php-reading-spec` skill. In a single-tenant service, or on a table with no tenant
      discriminator, there is no tenant to isolate and this item is skipped rather than failed.
- [ ] Read endpoint validates input and translates errors, exhaustively.
- [ ] Read error stack produced: the `Query/Shared/Http` `ExceptionMapping`, registered on the one error middleware
      beside the write-side mapping rather than as a second handler, per `php-hex-query`.
- [ ] Database adapter covered by an integration test.
- [ ] Runnable check: the scope suite passes and the style hook is clean.

## Does not do

- Does not build the write side: the Command, the Handler, or a write endpoint, route, or webhook.
- Does not model the application inner hexagon or persist an aggregate on the Driven side.
- Does not change schema or add a migration.
- Does not author the read tests by hand. The read slice delegates them to `php-writing-tests`.
