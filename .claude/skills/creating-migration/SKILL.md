---
name: creating-migration
description: Build a forward-only, one-change-per-file Flyway migration. Use when creating a migration, adding a table or column, altering schema, running a backfill, or seeding initial rows in a PHP service.
---

# Add migration

Builds a Flyway migration. Routes to the migration rule, and carries the canonical SQL shapes and the generative how-to
that used to live inside the rule.

Target: the change described in the request (e.g. `create payments table`).

## When to use

- Create a migration, add a table or column, alter schema, run a backfill, or seed initial rows.

## When NOT to use

- Write application code around the schema: use the layer action (e.g. `php-creating-driven`).
- Run the migration: this skill writes the file only, it does not apply it.
- No schema change is involved (a pure read slice or in-memory behavior): stay in the layer skills, a migration that
  touches nothing is not warranted.
- Bootstrap the repository layout or a config file: that is the static skeleton, use `php-creating-skeleton`.

## Pragmatic stance

Write the smallest forward-only change the request needs, and split rather than combine. One logical change per file,
the schema only ever moves forward, and a destructive or `NOT NULL` change becomes a multiphase sequence (per
`database-migration`) instead of one risky file. Reach for a backfill or a seed only once the DDL that makes it valid is
already in place.

## Rules applied

Load and follow, in this priority. Where a bullet routes to the spec at `<spec-root>`, that spec overrides the stated
default where it speaks, and with `<spec-root>` unset the stated default is the whole rule.

- Migration policy (file numbering, forward-only, one logical change per file, backfill, the two-phase destructive and
  `NOT NULL` sequence): stated in `database-migration` § Migration policy, and overridden by the spec at `<spec-root>`
  where it speaks. Read that spec directly, and in a PHP repository read it through the `php-reading-spec` skill.
- Column types (the fixed-width binary UUIDv7 identifier, the microsecond timestamp, minor-unit integer money, closed
  sets): stated in `database-migration` § Column types and engine as a portable intent plus its `<database-engine>`
  spelling, and overridden by the spec at `<spec-root>` where it speaks.
- `database-migration`: the data-classification tags, the column-comment prose, the index and constraint naming, and the
  soft-delete rule.
- The DDL `CREATE` and `ALTER` shape: `references/` in this skill.

## Shapes and how-to (read when scaffolding)

The literal shapes and decision aids that the rule used to inline live here, surfaced only when this skill runs. They
are written in the `<database-engine>` = `mysql` binding of the neutral shape that `database-migration` states (§
Statement shape and § Column types and engine). On another engine the shape and the ordering hold unchanged, and only
the type and table-level spellings become that engine's equivalents.

- `references/create-table.md`: the canonical `CREATE TABLE` (orders, with foreign keys, explicit indexes, unique and
  check constraints, engine block).
- `references/create-table-classified.md`: the classified-columns variant (PII tags and the table-level tag union).
- `references/alter-table.md`: the combined `ALTER TABLE` shape with `AFTER` placement.
- `references/seed-insert.md`: the create-plus-seed shape for initial data.
- `references/column-ordering.md`: the two-level prefix grouping algorithm, representative selection, the
  prefix-collision worked example, and `AFTER` placement in `ALTER TABLE`.
- `references/on-delete-actions.md`: the `CASCADE` vs. `RESTRICT` vs. `SET NULL` vs. `NO ACTION` decision aid, with the
  default-to-`RESTRICT` fallback.
- `references/collation.md`: the per-column collation decision tree (binary vs. human-readable vs. technical vs.
  regulated text) and worked examples.

## Assembly order

1. The directory the project's migration runner actually reads. `database/migrations/` is the canonical home, but
   confirm it against the runner's configured location before writing anything (the `Makefile` target that runs the
   migration, the runner's locations variable, the compose mount). Where they disagree, write the file beside the
   migrations the project already applies and report the divergence as a finding. Opening a second directory splits the
   history and produces a file the runner never applies, which surfaces much later as a mystery.
2. Next version number (highest applied plus one, with no gap or conflict).
3. One logical change per file.
4. SQL per `database-migration` and this skill's `references/`. Start the table or alter from `references/`.
5. Backfill or seed in its own migration, separate from the DDL.

## Completeness gate

- [ ] Forward-only: no already-applied migration was altered.
- [ ] One logical change per file, named per `database-migration` § Migration policy, and per the spec at `<spec-root>`
      where it overrides that default.
- [ ] SQL per `database-migration` and this skill's `references/`.
- [ ] Statement shape per `database-migration` § Statement shape: the closing `)` alone on its line, one space after
      each index and constraint keyword, column definitions vertically aligned.
- [ ] Destructive or NOT NULL changes follow the multiphase sequence in `database-migration` § Migration policy, and the
      spec at `<spec-root>` where it overrides that default.

## Does not do

- Does not apply or run the migration: it writes the file only.
- Does not generate the application code that reads or writes the new schema.
