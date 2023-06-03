<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace PointsOfInterest\Driven\PointsOfInterest\Repository;

use Doctrine\DBAL\Connection;
use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;
use PointsOfInterest\Domain\Ports\Outbound\Points;

final class Adapter implements Points
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function save(PointOfInterest $pointOfInterest): void
    {
        $this->connection->executeQuery(
            Queries::INSERT,
            [
                $pointOfInterest->name->value,
                $pointOfInterest->xCoordinate->value,
                $pointOfInterest->yCoordinate->value
            ]
        );
    }

    public function find(PointOfInterest $pointOfInterest): ?PointOfInterest
    {
        $result = $this->connection
            ->executeQuery(
                Queries::FIND,
                [
                    $pointOfInterest->name->value,
                    $pointOfInterest->xCoordinate->value,
                    $pointOfInterest->yCoordinate->value
                ]
            )
            ->fetchAssociative();

        if (empty($result)) {
            return null;
        }

        return PointOfInterest::from(
            name: $result['name'],
            xCoordinate: $result['xCoordinate'],
            yCoordinate: $result['yCoordinate']
        );
    }

    public function findAll(): PointsOfInterest
    {
        $result = $this->connection->executeQuery(Queries::FIND_ALL, [])->fetchAllAssociative();
        $mapper = fn(array $result) => PointOfInterest::from(
            name: $result['name'],
            xCoordinate: $result['xCoordinate'],
            yCoordinate: $result['yCoordinate']
        );

        return new PointsOfInterest(values: array_map($mapper, $result));
    }
}
