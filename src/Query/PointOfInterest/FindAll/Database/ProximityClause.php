<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll\Database;

use PointsOfInterest\Query\PointOfInterest\FindAll\Proximity;
use TinyBlocks\HttpQuery\Clause\SqlClause;

final readonly class ProximityClause implements SqlClause
{
    private const string PREDICATE = '(poi.x_coordinate BETWEEN :proximityMinimumX AND :proximityMaximumX
             AND poi.y_coordinate BETWEEN :proximityMinimumY AND :proximityMaximumY
             AND POWER(CAST(poi.x_coordinate AS SIGNED) - :proximityX, 2)
               + POWER(CAST(poi.y_coordinate AS SIGNED) - :proximityY, 2) <= :proximityRadiusSquared)';

    private function __construct(private array $bindings, private string $fragment)
    {
    }

    public static function from(?Proximity $proximity): ProximityClause
    {
        if (is_null($proximity)) {
            return new ProximityClause(bindings: [], fragment: '');
        }

        return new ProximityClause(bindings: [
            'proximityX'             => $proximity->xCoordinate,
            'proximityY'             => $proximity->yCoordinate,
            'proximityMinimumX'      => $proximity->minimumX(),
            'proximityMaximumX'      => $proximity->maximumX(),
            'proximityMinimumY'      => $proximity->minimumY(),
            'proximityMaximumY'      => $proximity->maximumY(),
            'proximityRadiusSquared' => $proximity->radiusSquared()
        ], fragment: ProximityClause::PREDICATE);
    }

    public function sql(): string
    {
        return $this->fragment;
    }

    public function isEmpty(): bool
    {
        return $this->fragment === '';
    }

    public function parameters(): array
    {
        return $this->bindings;
    }
}
