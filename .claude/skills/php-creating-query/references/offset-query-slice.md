# Offset query slice

Use only for a bounded reference collection that stays small and fully countable (payment methods, routing rules),
pairing a slice-specific `COUNT` with the shared projection.

```php
<?php

declare(strict_types=1);

// The offset slice layout for a bounded reference collection that stays small and fully countable
// (payment methods, routing rules). Worked from the real FindAll slice. The slice keeps a
// slice-specific Queries class only for the COUNT, and composes the shared projection for the page.
// Keyset is mandatory wherever the row count grows without bound. Reach for offset only for bounded
// reference lists.

// ---------------------------------------------------------------------------------------------
// src/Query/<Context>/<UseCase>/Database/<Resource>OffsetQuery.php

final readonly class PaymentMethodsOffsetQuery
{
    private function __construct(public string $countSql, public string $selectSql, public array $parameters)
    {
    }

    public static function from(int $limit, int $offset, array $orders, array $comparisons): PaymentMethodsOffsetQuery
    {
        $columns = FilterColumns::create()
            ->plain(field: 'code', column: 'pme.code')
            ->plain(field: 'name', column: 'pme.name')
            ->boolean(field: 'is_active', column: 'pme.is_active');

        $filters = Filters::from(columns: $columns, comparisons: $comparisons);
        $where = $filters->isEmpty() ? '' : sprintf(' WHERE %s', $filters->sql());

        $orderBy = SortClause::from(orders: $orders, columns: $columns);
        $template = ' ORDER BY %s LIMIT %d OFFSET %d';
        $window = sprintf($template, $orderBy->sql(), $limit, $offset);

        $countSql = sprintf('%s%s', Queries::COUNT, $where);
        $selectSql = sprintf('%s%s%s', Queries::BASE, $where, $window);

        return new PaymentMethodsOffsetQuery(
            countSql: $countSql,
            selectSql: $selectSql,
            parameters: $filters->parameters()
        );
    }
}

// ---------------------------------------------------------------------------------------------
// src/Query/<Context>/<UseCase>/Database/Queries.php  (slice-specific SQL only, the COUNT)
// The slice never restates the shared projection. It keeps only its own slice-specific SQL.

final readonly class Queries
{
    public const string COUNT = '
        SELECT COUNT(*) AS total
        FROM payment_methods AS pme
    ';
}
```
