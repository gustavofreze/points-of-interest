<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll\Database;

use Doctrine\DBAL\Connection;
use PointsOfInterest\Query\PointOfInterest\FindAll\PointsOfInterestFinding;
use PointsOfInterest\Query\PointOfInterest\FindAll\Proximity;
use PointsOfInterest\Query\PointOfInterest\FindAll\ReadModel\PointOfInterest;
use TinyBlocks\HttpQuery\Cursor\Keyset;
use TinyBlocks\HttpQuery\Cursor\Page;

final readonly class PointsOfInterestFindingAdapter implements PointsOfInterestFinding
{
    public function __construct(private Connection $connection)
    {
    }

    public function findAll(Keyset $keyset, ?Proximity $proximity, array $comparisons): Page
    {
        $query = PointsOfInterestKeysetQuery::from(
            keyset: $keyset,
            proximity: $proximity,
            comparisons: $comparisons
        );

        $rows = $this->connection
            ->executeQuery(sql: $query->sql, params: $query->parameters)
            ->fetchAllAssociative();

        return $keyset
            ->page(items: $rows)
            ->map(transformation: static function (array $row): array {
                return PointOfInterest::from(
                    id: (string)$row['id'],
                    name: (string)$row['name'],
                    createdAt: (string)$row['created_at'],
                    xCoordinate: (int)$row['x_coordinate'],
                    yCoordinate: (int)$row['y_coordinate']
                )->toArray();
            });
    }
}
