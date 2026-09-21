# Keyset query slice

Use when the read returns an unbounded collection that grows with traffic, so the page is a forward-only keyset (cursor)
seek narrowed by a scope.

The sample names the narrowing predicate a scope and nothing more, because the shape does not depend on what the scope
is. On a tenant-scoped table the scope is the tenant and its column is that table's tenant discriminator. In a
single-tenant service, or on a table that carries no such column, the scope is empty for every read, the adapter passes
`null`, and the slice still works unchanged.

## Contents

- `PaymentsFinding`: the read port at the slice root.
- `PaymentsFindingAdapter`: runs the SQL and maps rows to a `Page` of serialized views.
- `PaymentsKeysetQuery`: builds the seek SQL from the library cursor, the filters, and the scope.
- `PaymentsScope`: the scope predicate as a library `SqlClause`.

```php
<?php

declare(strict_types=1);

// The keyset (cursor) slice layout for an unbounded collection that grows with traffic.
// The only per-slice classes are the <Resource>KeysetQuery (builds the seek SQL) and the
// <Resource>Scope (scope predicate). SeekClause, SortClause, Filters, Every, and FilterColumns are
// tiny-blocks/http-query library types, NOT per-slice files. There is no <Resource>SeekClause or
// <Resource>SeekOrder file. The scope is neutral here on purpose, see the note below the sample.

// ---------------------------------------------------------------------------------------------
// src/Query/<Context>/<UseCase>/<Resource>Finding.php  (the read PORT, at the slice root)

/**
 * Finding of a forward-only cursor page of payments narrowed by a scope.
 */
interface PaymentsFinding
{
    /**
     * Finds the next cursor page of payments matching the keyset and the filter comparisons.
     *
     * @param Keyset $keyset The keyset carrying the page size, the orders, and the incoming cursor.
     * @param list<Comparison> $comparisons The validated filter comparisons.
     * @param string|null $scopeId The scope value, or null for the unscoped read.
     * @return Page The cursor page carrying the payment views and the next cursor.
     */
    public function findByScope(Keyset $keyset, array $comparisons, ?string $scopeId): Page;
}

// ---------------------------------------------------------------------------------------------
// src/Query/<Context>/<UseCase>/Database/<Resource>FindingAdapter.php
// Runs the SQL, maps rows inside the adapter, returns a Page of serialized views. Never a raw row.

final readonly class PaymentsFindingAdapter implements PaymentsFinding
{
    public function __construct(private Connection $connection)
    {
    }

    public function findByScope(Keyset $keyset, array $comparisons, ?string $scopeId): Page
    {
        $query = PaymentsKeysetQuery::from(
            keyset: $keyset,
            comparisons: $comparisons,
            scopeId: $scopeId
        );

        $rows = $this->connection
            ->executeQuery(sql: $query->sql, params: $query->parameters)
            ->fetchAllAssociative();

        return $keyset
            ->page(items: $rows)
            ->map(transformation: static function (array $row): array {
                return PaymentMapper::from(record: $row, revealsBrcode: false)->toPayment()->toArray();
            });
    }
}

// ---------------------------------------------------------------------------------------------
// src/Query/<Context>/<UseCase>/Database/<Resource>KeysetQuery.php
// Builds the seek SQL from the library Keyset cursor, the FilterColumns map, the Scope, and the
// library SeekClause plus SortClause. Composes the shared projection (Queries::BASE). The index
// hint names the covering index of the scoped page, worked here on this service's own table and
// alias (payments AS pay) and its own scope column (organization_id). Another slice swaps those
// three for its own projection, never for another service's schema. See the note below the sample.

final readonly class PaymentsKeysetQuery
{
    private const string PARTITION_INDEX_HINT = 'FORCE INDEX (idx_payments_organization_id_created_at_id)';

    private function __construct(public string $sql, public array $parameters)
    {
    }

    public static function from(Keyset $keyset, array $comparisons, ?string $scopeId): PaymentsKeysetQuery
    {
        $columns = FilterColumns::create()
            ->plain(field: 'status', column: 'pay.status')
            ->plain(field: 'context', column: 'poc.code')
            ->plain(field: 'created_at', column: 'pay.created_at')
            ->wrapped(field: 'id', column: 'pay.id', binding: 'UUID_TO_BIN(%s)');

        $scope = PaymentsScope::from(scopeId: $scopeId);
        $filters = Filters::from(columns: $columns, comparisons: $comparisons);
        $seek = SeekClause::from(keyset: $keyset, columns: $columns);

        $predicate = Every::of($scope, $filters, $seek);

        $sort = SortClause::from(orders: $keyset->orders(), columns: $columns);
        $limit = $keyset->limit()->plusOne();

        $where = $predicate->isEmpty() ? '' : sprintf(' WHERE %s', $predicate->sql());

        $isUnfilteredScopePage = !$scope->isEmpty() && $filters->isEmpty() && $seek->isEmpty();
        $from = $isUnfilteredScopePage
            ? preg_replace('/FROM payments\s+AS pay/', '$0 ' . self::PARTITION_INDEX_HINT, Queries::BASE)
            : Queries::BASE;

        $template = '%s%s ORDER BY %s LIMIT %d';
        $sql = sprintf($template, $from, $where, $sort->sql(), $limit->toInteger());

        return new PaymentsKeysetQuery(sql: $sql, parameters: $predicate->parameters());
    }
}

// ---------------------------------------------------------------------------------------------
// src/Query/<Context>/<UseCase>/Database/<Resource>Scope.php
// The scope predicate as a library SqlClause. Empty when the read is unscoped (null scope),
// otherwise the single equality on the scope column. This is the scoping invariant in code: the
// predicate is built here and nowhere else, so no slice can forget it or write it a second way.

final readonly class PaymentsScope implements SqlClause
{
    private function __construct(private string $sql, private array $parameters)
    {
    }

    public static function from(?string $scopeId): PaymentsScope
    {
        if (is_null($scopeId)) {
            return new PaymentsScope(sql: '', parameters: []);
        }

        return new PaymentsScope(
            sql: 'pay.organization_id = UUID_TO_BIN(:organization_id)',
            parameters: ['organization_id' => $scopeId]
        );
    }

    public function sql(): string
    {
        return $this->sql;
    }

    public function isEmpty(): bool
    {
        return $this->sql === '';
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}
```

## Note, the concrete scope in this service

The `payments` table is tenant-scoped, so the scope is the tenant and the sample's scope column is that table's tenant
discriminator, `organization_id`. The port method reads `findByOrganization`, the parameter is `$organizationId`, the
projection is `payments AS pay`, and the covering index is `idx_payments_organization_id_created_at_id`. Those names
belong to this service, they are not part of the shape. Another slice replaces the table, the alias, and the scope
column throughout the sample (the `FilterColumns` entries, the `FORCE INDEX` constant, the `FROM` pattern, and the scope
predicate) with its own projection, and every one of them has to move together. In a single-tenant service, or on a
table with no tenant discriminator, `PaymentsScope` is empty for every read, the index hint branch never fires, and the
slice is otherwise identical.
