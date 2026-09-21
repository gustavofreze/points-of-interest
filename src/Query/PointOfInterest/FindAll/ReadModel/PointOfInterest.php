<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll\ReadModel;

final readonly class PointOfInterest
{
    private function __construct(public string $id, public string $name, public Point $point, public string $createdAt)
    {
    }

    public static function from(
        string $id,
        string $name,
        string $createdAt,
        int $xCoordinate,
        int $yCoordinate
    ): PointOfInterest {
        return new PointOfInterest(
            id: $id,
            name: $name,
            point: Point::from(xCoordinate: $xCoordinate, yCoordinate: $yCoordinate),
            createdAt: $createdAt
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'point'      => $this->point->toArray(),
            'created_at' => $this->createdAt
        ];
    }
}
