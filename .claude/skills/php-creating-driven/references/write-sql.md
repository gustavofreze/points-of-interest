# Write-side SQL shapes

Use for the INSERT, UPDATE, and DELETE shapes on a tenant-scoped table, each carrying that table's tenant discriminator,
with UUID values flowing through UUID_TO_BIN.

Two invariants live in these shapes and they are not the same claim. The mandatory WHERE on UPDATE and DELETE always
holds, on every table, in every service. The tenant scope holds only where there is a tenant: in a single-tenant
service, or on a table with no tenant discriminator, the scope column is dropped from the shapes below and the scoping
gate is skipped rather than failed. The worked examples below name this project's column, `organization_id`.

```sql
# Write-side SQL shapes. Pure SQL without the surrounding PHP class. When placed in a Queries
# constant, the SQL body is indented 8 spaces inside the PHP string literal (see queries-class.md).
# INSERT lists columns explicitly, ordered by name length ascending with id first. UPDATE aligns
# the = signs vertically inside SET. DELETE always carries a WHERE. UUID values flow through
# UUID_TO_BIN. The table's tenant discriminator is present in every INSERT column list and in the
# WHERE of every UPDATE and DELETE on a tenant-scoped table. A single-tenant service, or a table
# without one, has no such column, and the mandatory WHERE on UPDATE and DELETE still holds without it.

# ---------------------------------------------------------------------------------------------
# INSERT. Columns listed explicitly, id first then ascending by name length. UUIDs via UUID_TO_BIN.

INSERT INTO payments (id, amount, status, currency, provider, charge_id, organization_id)
VALUES (UUID_TO_BIN(:id), :amount, :status, :currency, :provider, UUID_TO_BIN(:charge_id),
        UUID_TO_BIN(:organization_id))

# ---------------------------------------------------------------------------------------------
# UPDATE. The = signs align vertically inside SET. WHERE is mandatory and carries organization_id.

UPDATE payments AS pay
SET pay.status     = :status,
    pay.provider   = :provider,
    pay.updated_at = :updated_at
WHERE pay.id = UUID_TO_BIN(:id)
  AND pay.organization_id = UUID_TO_BIN(:organization_id)

# ---------------------------------------------------------------------------------------------
# DELETE. Always a WHERE clause (unconditional deletes are prohibited), scoped by organization_id.

DELETE
FROM payments AS pay
WHERE pay.id = UUID_TO_BIN(:id)
  AND pay.organization_id = UUID_TO_BIN(:organization_id)
```
