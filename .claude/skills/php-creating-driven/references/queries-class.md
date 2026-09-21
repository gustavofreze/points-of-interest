# Queries class shape

Use for the SQL-owning class shared by the write side (repository) and the read side, holding typed public const string
constants ordered by name length ascending.

```php
<?php

declare(strict_types=1);

// The Queries class shape, shared by the write side (repository) and the read side (slice or
// context projection). A final readonly class with public const string constants, ordered by name
// length ascending. The SQL body is indented 8 spaces inside the PHP string literal. Static SQL
// uses single quotes, and double quotes only when the SQL contains single-quoted string literals
// (for example 'active'). Dynamic values flow through bind parameters, never sprintf or interpolation.

final readonly class Queries
{
    public const string INSERT = '
        INSERT INTO payments (id, amount, status, currency, provider, charge_id, organization_id)
        VALUES (UUID_TO_BIN(:id), :amount, :status, :currency, :provider, UUID_TO_BIN(:charge_id), UUID_TO_BIN(:organization_id))
    ';

    public const string FIND_BY_ID = '
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
    ';
}
```
