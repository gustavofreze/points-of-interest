# Alter table

Use this when a migration adds columns or constraints to an existing table, placing each new column with AFTER.

```sql
-- ALTER TABLE shape. Multiple statements representing a single logical change
-- may be combined. Use AFTER <column> (or FIRST) to place each new column inside
-- its group/subgroup, and place business TIMESTAMP(6) columns immediately before
-- the audit columns. Without AFTER, MySQL appends at the end, breaking both the
-- grouping invariant and the audit-columns-last invariant.

ALTER TABLE orders
    ADD COLUMN dispatch_id BINARY(16)   NULL COMMENT '[NONE] The dispatch identifier in Version 7 UUID format (e.g., 01912d42-3d4e-75f6-a7b8-91a2b3c4d5e6).' AFTER customer_id,
    ADD COLUMN dispatched_at TIMESTAMP(6) NULL COMMENT '[NONE] The UTC date and time when the order was dispatched in ISO 8601 format (e.g., 2026-02-13T08:49:44.931408+00:00).' AFTER dispatch_id,
    ADD KEY idx_orders_dispatch_id (dispatch_id),
    ADD CONSTRAINT fk_orders_dispatch_id FOREIGN KEY (dispatch_id) REFERENCES dispatches (id) ON
DELETE
SET NULL;
```

## Evolving an ENUM column

When the change adds a value to an `ENUM` column, use `MODIFY COLUMN` restating the full definition (including the
generation expression when the column is generated, and the `COMMENT` with its classification tag), and append the new
value at the END of the list. Never reorder or remove existing values: appending preserves the stored ordinal of every
existing value, and on a non-generated column it keeps the change eligible for the in-place algorithm. Update the
`(e.g., ...)` list in the `COMMENT` to include the new value in its lifecycle position.
