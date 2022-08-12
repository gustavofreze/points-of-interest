<?php

namespace PointsOfInterest\Domain\Models;

use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectAdapter;

final class PointsOfInterest implements ValueObject
{
    use ValueObjectAdapter;

    /**
     * @param PointOfInterest[] $values
     */
    public function __construct(public readonly array $values)
    {
    }

    public function byProximity(ReferencePoint $referencePoint, Distance $maximumDistance): PointsOfInterest
    {
        $pointsOfInterest = [];

        foreach ($this->values as $pointOfInterest) {
            if ($pointOfInterest->isCloseTo(referencePoint: $referencePoint, maximumDistance: $maximumDistance)) {
                $pointsOfInterest[] = $pointOfInterest;
            }
        }

        return new PointsOfInterest(values: $pointsOfInterest);
    }
}
