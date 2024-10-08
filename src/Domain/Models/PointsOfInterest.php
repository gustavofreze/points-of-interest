<?php

declare(strict_types=1);

namespace PointsOfInterest\Domain\Models;

use TinyBlocks\Collection\Collection;

final class PointsOfInterest extends Collection
{
    public function byProximity(ReferencePoint $referencePoint, Distance $maximumDistance): PointsOfInterest
    {
        $pointsOfInterest = $this->filter(
            predicates: fn(PointOfInterest $pointOfInterest): bool => $pointOfInterest->isCloseTo(
                referencePoint: $referencePoint,
                maximumDistance: $maximumDistance
            )
        );

        return PointsOfInterest::createFrom(elements: $pointsOfInterest);
    }
}
