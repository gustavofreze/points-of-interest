<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll\Database;

use PointsOfInterest\Query\PointOfInterest\FindAll\Proximity;
use TinyBlocks\HttpQuery\Clause\Every;
use TinyBlocks\HttpQuery\Clause\FilterColumns;
use TinyBlocks\HttpQuery\Clause\Filters;
use TinyBlocks\HttpQuery\Clause\SeekClause;
use TinyBlocks\HttpQuery\Clause\SortClause;
use TinyBlocks\HttpQuery\Cursor\Keyset;

final readonly class PointsOfInterestKeysetQuery
{
    private function __construct(public string $sql, public array $parameters)
    {
    }

    public static function from(Keyset $keyset, ?Proximity $proximity, array $comparisons): PointsOfInterestKeysetQuery
    {
        $columns = FilterColumns::create()
            ->plain(field: 'name', column: 'poi.name')
            ->plain(field: 'created_at', column: 'poi.created_at')
            ->plain(field: 'x_coordinate', column: 'poi.x_coordinate')
            ->plain(field: 'y_coordinate', column: 'poi.y_coordinate')
            ->wrapped(field: 'id', column: 'poi.id', binding: 'UUID_TO_BIN(%s)');

        $filters = Filters::from(columns: $columns, comparisons: $comparisons);
        $seek = SeekClause::from(keyset: $keyset, columns: $columns);
        $nearby = ProximityClause::from(proximity: $proximity);

        $predicate = Every::of($seek, $filters, $nearby);

        $sort = SortClause::from(orders: $keyset->orders(), columns: $columns);
        $limit = $keyset->limit()->plusOne();

        $where = $predicate->isEmpty() ? '' : sprintf('WHERE %s', $predicate->sql());

        $template = '%s %s ORDER BY %s LIMIT %d';
        $sql = sprintf($template, Queries::BASE, $where, $sort->sql(), $limit->toInteger());

        return new PointsOfInterestKeysetQuery(sql: $sql, parameters: $predicate->parameters());
    }
}
