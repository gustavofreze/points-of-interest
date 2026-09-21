<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Models\PointOfInterest;

use PointsOfInterest\Application\Domain\Events\Commons\EventualAggregateRoot;
use PointsOfInterest\Application\Domain\Events\Commons\EventualAggregateRootBehavior;
use PointsOfInterest\Application\Domain\Events\PointOfInterestRegistered;
use PointsOfInterest\Application\Domain\Models\Commons\Name;

final class PointOfInterest implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    private function __construct(public PointOfInterestId $id, public Name $name, public Coordinates $coordinates)
    {
    }

    public static function register(PointOfInterestId $id, Name $name, Coordinates $coordinates): PointOfInterest
    {
        $pointOfInterest = new PointOfInterest(id: $id, name: $name, coordinates: $coordinates);

        $pointOfInterest->pushEvent(event: new PointOfInterestRegistered(name: $name, coordinates: $coordinates));

        return $pointOfInterest;
    }
}
