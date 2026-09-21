# Column ordering algorithm

How to order the business columns of a table. This file fixes the column-ordering invariant (id first, then non-
`TIMESTAMP` business columns grouped by prefix, then business `TIMESTAMP(6)` columns, then audit columns last) and the
full mechanics behind it. The database-migration rule delegates the column-ordering how-to here.

## The order

1. `id` first (when present).
2. Non-`TIMESTAMP` business columns, grouped by entity prefix and then ordered (see § Grouping).
3. Business `TIMESTAMP(6)` columns (domain timestamps such as `occurred_at`, `since`, `expires_at`, distinct from the
   audit columns `created_at` and `updated_at`) by the same grouping and ordering rule.
4. Audit columns (`created_at`, `updated_at` when applicable) always last.

## Grouping (up to two levels)

- Group columns by their leading snake_case token (level 1, e.g. `order_*`, `charge_*`, `payment_*`). Within a level-1
  group, subgroup by the first two tokens (level 2, e.g. `order_amount_*`, `payment_method_*`).
- A group or subgroup exists only when two or more columns share the prefix. A column whose prefix is unique is
  standalone (a group of one). Never group beyond two tokens.
- Grouping is by full snake_case tokens, not character prefix (e.g. `organization_id` is not part of the `order` group).

## Ordering within and between groups

- **Within a subgroup**, order by name length ascending, with alphabetical order as the tiebreaker (e.g., `currency`
  before `quantity`).
- **Between subgroups inside a level-1 group, and between level-1 groups and standalones**, order by each group's
  representative, defined as its shortest member after the intra-group sort, using the same length-ascending then
  alphabetical rule. This keeps every group and subgroup physically contiguous while preserving the short-first
  ordering.

## Worked example (prefix collision)

With columns `order_id`, `order_total_value`, `order_total_currency`, `order_amount_value`, `order_amount_currency`, the
level-2 subgroups `order_total` and `order_amount` keep each value/currency pair contiguous instead of interleaving them
by raw length:

```
order_id
order_total_value
order_total_currency
order_amount_value
order_amount_currency
```

## Placement in ALTER TABLE

In `ALTER TABLE ... ADD COLUMN`, use `AFTER <column>` (or `FIRST`) to place the new column inside its group or subgroup
and preserve the ordering. For a business `TIMESTAMP(6)` column, place it immediately before the audit columns. Without
`AFTER`, MySQL appends at the end, which breaks both the grouping invariant and the audit-columns-last invariant when
the table has audit columns.
