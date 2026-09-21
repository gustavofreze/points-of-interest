# Create table with seed

Use this when a newly created table ships with bootstrap rows, seeding them in the same migration file as the CREATE
TABLE.

Seed only non-sensitive reference data. Never insert real personal or secret values (emails, passwords, tokens, document
numbers, card data) in a seed or insert migration: the file is committed to version control and replayed in every
environment, so any secret in it leaks. Real per-user and secret data is created at runtime, never seeded.

```sql
-- Initial data alongside CREATE TABLE. When a newly created table ships with
-- bootstrap rows (default roles, canonical currencies, seed configuration), the
-- INSERT statements live in the same migration file as the CREATE TABLE, placed
-- immediately after the table-creation block. The whole file is one logical
-- change: create the table and populate the rows it depends on.
--
-- Seed only non-sensitive reference data (roles, currencies, config). Never seed
-- real personal or secret values (emails, passwords, tokens, document numbers,
-- card data), the file is committed and replayed in every environment.
--
-- Seed UUIDs are literal Version 7 values via UUID_TO_BIN('<literal>') without
-- the swap flag, so the migration is deterministic across environments.
--
-- A data-only migration against an already-existing table (backfill, corrective
-- update, late-added seed) instead lives in its own dedicated file, targets
-- exactly one table, and contains no schema changes.

CREATE TABLE roles
(
    id         BINARY(16)   NOT NULL COMMENT '[NONE] The role identifier in Version 7 UUID format (e.g., 0190d09e-a7a8-7e89-b48c-09fff0f0f0f0).',
    name       VARCHAR(50)  NOT NULL COMMENT '[NONE] The role name (e.g., admin, member, viewer).',
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '[NONE] The UTC date and time when the record was created in ISO 8601 format (e.g., 2026-02-13T08:49:44.931408+00:00).',
    PRIMARY KEY (id),
    CONSTRAINT unq_roles_name UNIQUE (name)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_0900_ai_ci
    COMMENT = 'Table used to persist user roles.';

INSERT INTO roles (id, name)
VALUES (UUID_TO_BIN('11111111-1111-7111-8111-111111111111'), 'admin'),
       (UUID_TO_BIN('22222222-2222-7222-8222-222222222222'), 'member'),
       (UUID_TO_BIN('33333333-3333-7333-8333-333333333333'), 'viewer');
```
