<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointsOfInterest\Repository;

use Doctrine\DBAL\Connection;
use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;
use PointsOfInterest\Domain\Ports\Outbound\Points;

final readonly class Adapter implements Points
{
    public function __construct(private Connection $connection)
    {
    }

    public function save(PointOfInterest $pointOfInterest): void
    {
        $this->connection->executeQuery(Queries::INSERT, [
            $pointOfInterest->name->value,
            $pointOfInterest->xCoordinate->value,
            $pointOfInterest->yCoordinate->value
        ]);
    }

    public function find(PointOfInterest $pointOfInterest): ?PointOfInterest
    {
        $result = $this->connection
            ->executeQuery(Queries::FIND, [
                $pointOfInterest->name->value,
                $pointOfInterest->xCoordinate->value,
                $pointOfInterest->yCoordinate->value
            ])
            ->fetchAssociative();

        if (empty($result)) {
            return null;
        }

        return PointOfInterest::from(
            name: (string)$result['name'],
            xCoordinate: (int)$result['xCoordinate'],
            yCoordinate: (int)$result['yCoordinate']
        );
    }

    public function findAll(): PointsOfInterest
    {
        $result = $this->connection
            ->executeQuery(Queries::FIND_ALL)
            ->fetchAllAssociative();

        return PointsOfInterest::createFrom(elements: $result)
            ->map(transformations: fn(array $item) => PointOfInterest::from(
                name: (string)$item['name'],
                xCoordinate: (int)$item['xCoordinate'],
                yCoordinate: (int)$item['yCoordinate']
            ));
    }
}
