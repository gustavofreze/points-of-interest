<?php

declare(strict_types=1);

namespace Test\Unit;

use PointsOfInterest\Application\Domain\Models\Commons\Name;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\Coordinates;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterest;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterestId;

final class PointOfInterestFactory
{
    public static function registered(): PointOfInterest
    {
        return PointOfInterest::register(
            id: PointOfInterestId::generate(),
            name: Name::from(value: 'Pub'),
            coordinates: Coordinates::from(xCoordinate: 12, yCoordinate: 8)
        );
    }
}
