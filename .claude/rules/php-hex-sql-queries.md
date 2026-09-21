---
description: SQL lives only in a final readonly Queries class. The structural residue only, no sprintf or interpolation (bind parameters), DELETE and UPDATE always carry a WHERE, and the connection negotiates a full Unicode encoding. Formatting, aliasing, the collation decision tree, and worked examples live in the php-creating-driven and php-creating-query skills. Every query over a tenant-scoped table filters by that table's tenant discriminator, and a table without one, like a single-tenant service, skips that gate rather than failing it.
paths:
    - "src/Driven/**/*Query.php"
    - "src/Driven/**/Queries.php"
    - "src/Query/**/*Query.php"
    - "src/Query/**/Queries.php"
---

# SQL queries

All SQL lives exclusively in a `Queries` class. The placement of that class (the repository directory on the write side,
`Query/<Context>/Shared/Database/` or a slice's own `Database/` on the read side) is owned by
`php-hex-driven-repository` and `php-hex-query`.

## Pre-output checklist

1. SQL lives in a `final readonly class Queries` with `public const string` constants. See § Class structure.
2. Dynamic SQL building via `sprintf` or string interpolation is prohibited. Dynamic values flow through bind
   parameters. See § Class structure.
3. `DELETE` and `UPDATE` always carry a `WHERE` clause. Unconditional writes are prohibited. See § DELETE and UPDATE.
4. The connection negotiates a full Unicode encoding that carries four-byte characters, and a legacy partial-Unicode
   alias is prohibited. With `<database-engine>` = `mysql` that encoding is `utf8mb4`, and `SET NAMES utf8` (an alias of
   `utf8mb3`) is the prohibited form. See § Connection charset.
5. Every query over a tenant-scoped table filters by that table's tenant discriminator, the column carrying the tenant
   scope. A table that has no such column, and a single-tenant service, SKIP this gate rather than failing it. See §
   Tenant scoping.

## Class structure

`final readonly class Queries` with typed constants (`public const string`). Static SQL uses single quotes. Dynamic SQL
building via `sprintf` or string interpolation is prohibited: dynamic values flow through bind parameters, never
concatenated or formatted into the SQL text. The `Queries` class shape, the constant ordering, the formatting, the
aliasing, the `AS` rules, and the `INSERT` and `SELECT` shapes are in the php-creating-driven and php-creating-query
skills.

## DELETE and UPDATE

`DELETE` and `UPDATE` always carry a `WHERE` clause. An unconditional delete or update is prohibited.

## Connection charset

The portable invariant: the connection negotiates a full Unicode encoding once, in `src/Dependencies.php`, and every
query then traffics it. An encoding that cannot carry four-byte characters is never negotiated, whatever the engine
calls it, because a truncated emoji or CJK extension character is a data-loss bug and not a display bug. The encoding
declared on the schema is the same one, owned by `database-migration` § Column types and engine.

With `<database-engine>` = `mysql` that encoding is `utf8mb4`, set as `'charset' => 'utf8mb4'`. `SET NAMES utf8` is an
alias of `utf8mb3` on MySQL 8 and cannot carry 4-byte characters, so it is prohibited. The collation decision tree for
`JSON_TABLE` and collation-less derived string columns (the two comparison cases, the pin-versus-domain-invariant
choice, and the MySQL 8.4 verification) is MySQL semantics too, and it is in the php-creating-query skill.

## Tenant scoping

Every query over a tenant-scoped table filters by that table's tenant discriminator, and a read or a write that can
cross tenants is a defect, never a convenience. The discriminator is the column that carries the tenant scope on the
table being queried, and whoever writes the query is already looking at that table and can see whether it has one.
Scoping is decided per table, never assumed repository-wide: a discriminator on one table says nothing about the next.
Which tables are tenant-scoped, and any deliberate exception (a uniqueness constraint intentionally global, for
instance), are owned by the spec at `<spec-root>`, read it via the php-reading-spec skill. Where `<spec-root>` is
absent, ask which tables are tenant-scoped rather than guessing.

A table with no tenant discriminator has no column to scope by, and so does a service that is single-tenant throughout.
Either way this gate is skipped rather than failed.
