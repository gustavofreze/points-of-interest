# Read-side SQL shapes

Use for the single-table SELECT, the SELECT with JOIN, the multi-column ORDER BY, and the JSON-extraction shared
projection on a read over a tenant-scoped table.

Every read over a tenant-scoped table is filtered by that table's tenant discriminator, and that scope is the
multi-tenant isolation invariant, never an optional filter. The table you are querying tells you whether it has one. In
a single-tenant service, or on a table with no tenant discriminator, the scope predicate is dropped from the shapes
below and the scoping gate is skipped rather than failed. Everything else in the shapes (the explicit `AS` aliasing, the
vertical alignment, the uppercase keywords, the table alias on every column) holds either way.

UUIDs are stored binary and exposed textual, and that rule holds on any engine. `BIN_TO_UUID` and `UUID_TO_BIN` are the
`<database-engine>` binding of it. On another engine the pair is whatever that engine offers for the same conversion,
and the storage-versus-exposure split is what must survive.

```sql
# Read-side SQL shapes. Pure SQL without the surrounding PHP class. When placed in a Queries
# constant, the SQL body is indented 8 spaces inside the PHP string literal (see the Queries class
# shape in php-creating-driven/references/queries-class.md). Every column explicitly aliased
# with AS, AS aligned vertically, keywords UPPERCASE, every table aliased and every column prefixed,
# UUID columns through BIN_TO_UUID for output, and every read over a tenant-scoped table filtered by
# that table's tenant discriminator (organization_id in the worked examples here).

# ---------------------------------------------------------------------------------------------
# SELECT (single table). Alias name length ascending, id first.

SELECT BIN_TO_UUID(pay.id)              AS id,
       pay.amount                       AS amount,
       pay.status                       AS status,
       pay.currency                     AS currency,
       pay.provider                     AS provider,
       BIN_TO_UUID(pay.charge_id)       AS charge_id,
       BIN_TO_UUID(pay.organization_id) AS organization_id
FROM payments AS pay
WHERE pay.id = UUID_TO_BIN(:id)
  AND pay.organization_id = UUID_TO_BIN(:organization_id)

# ---------------------------------------------------------------------------------------------
# SELECT (with JOIN). INNER JOIN and LEFT JOIN sit flush-left at the same indent as FROM (no
# continuation indent). The join type is always qualified (INNER or LEFT, never a bare JOIN). The
# table-name AS aliases align vertically across FROM and every JOIN line. This projection joins
# three tables and uses semantic column grouping: id first, then identifiers, then names, then amounts.
# <tenant>_id is the hole for the table's tenant discriminator, whatever that table happens to call
# it. A table that carries none drops that projected column and that WHERE line.

SELECT BIN_TO_UUID(<agg>.id)          AS id,
       <agg>.<identifier>             AS <identifier>,
       <agg>.<status>                 AS <status>,
       <rel>.name                     AS <relation>_name,
       <ref>.code                     AS <reference>_code,
       BIN_TO_UUID(<agg>.<tenant>_id) AS <tenant>_id,
       <agg>.<amount>_value           AS <amount>_value,
       <agg>.<amount>_currency        AS <amount>_currency
FROM <collection>                  AS <agg>
INNER JOIN <relation_collection>   AS <rel> ON <rel>.id = <agg>.<relation>_id
LEFT JOIN <reference_collection>   AS <ref> ON <ref>.id = <agg>.<reference>_id
WHERE <agg>.id = UUID_TO_BIN(:id)
  AND <agg>.<tenant>_id = UUID_TO_BIN(:<tenant>_id)

# ---------------------------------------------------------------------------------------------
# Multi-column ORDER BY. Continuation columns indent to align under the first column. Order columns
# by name length ascending when it does not alter query behavior (ORDER BY on indexed columns must
# respect the index order).

SELECT BIN_TO_UUID(pay.id) AS id,
       pay.amount          AS amount,
       pay.status          AS status
FROM payments AS pay
WHERE pay.organization_id = UUID_TO_BIN(:organization_id)
ORDER BY pay.status,
         pay.created_at DESC

# ---------------------------------------------------------------------------------------------
# Worked shared projection (one service's Queries::BASE, JSON extraction from a payload column).
# The column names, the JSON paths, and the joined tables below are that service's schema. They are
# illustrative, never a canonical shape to copy. What generalizes is the composition: the slices of
# the context compose this BASE and append only their own WHERE / ORDER BY / LIMIT. The
# NULLIF and payload->> extractions feed the row Mapper, which coerces them into the typed read model.

SELECT BIN_TO_UUID(pay.id)                                            AS id,
       NULLIF(pay.payload->'$.charge.payer', CAST('null' AS JSON))    AS payer,
       pay.status                                                     AS status,
       pay.order_id                                                   AS order_id,
       pay.charge_id                                                  AS charge_id,
       ppr.name                                                       AS provider_name,
       pay.created_at                                                 AS created_at,
       pay.payload->>'$.charge.country'                               AS charge_country,
       poc.code                                                       AS order_context,
       ppr.code                                                       AS provider_code,
       pay.payload->'$.charge.payment_method'                         AS payment_method_details,
       NULLIF(pay.payload->>'$.charge.statement_descriptor', 'null')  AS statement_descriptor,
       pay.payload->>'$.order.description'                            AS order_description,
       BIN_TO_UUID(ppr.id)                                            AS provider_id,
       BIN_TO_UUID(pay.organization_id)                               AS organization_id,
       pay.payload->>'$.order.amount.amount'                         AS order_amount_value,
       pay.payload->>'$.charge.amount.amount'                        AS charge_amount_value,
       pay.payload->>'$.order.amount.currency'                       AS order_amount_currency,
       pay.payload->>'$.charge.amount.currency'                      AS charge_amount_currency
FROM payments                     AS pay
INNER JOIN payment_order_contexts AS poc ON poc.id = pay.payment_order_context_id
LEFT JOIN payment_providers       AS ppr ON ppr.id = pay.payment_provider_id
```
