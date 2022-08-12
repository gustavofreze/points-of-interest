<?php

namespace PointsOfInterest\Domain\Models;

use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectAdapter;

final class PointOfInterest implements ValueObject
{
    use ValueObjectAdapter;

    public function __construct(
        public readonly Name $name,
        public readonly Coordinate $xCoordinate,
        public readonly Coordinate $yCoordinate
    ) {
    }

    public static function from(string $name, int $xCoordinate, int $yCoordinate): PointOfInterest
    {
        return new PointOfInterest(
            name: new Name(value: $name),
            xCoordinate: new Coordinate(value: $xCoordinate),
            yCoordinate: new Coordinate(value: $yCoordinate)
        );
    }

    public function isCloseTo(ReferencePoint $referencePoint, Distance $maximumDistance): bool
    {
        $xValue = $this->xCoordinate
            ->subtract(subtrahend: $referencePoint->xCoordinate)
            ->squared();

        $yValue = $this->yCoordinate
            ->subtract(subtrahend: $referencePoint->yCoordinate)
            ->squared();

        $distance = $xValue
            ->add(addend: $yValue)
            ->squareRoot();

        return (new Distance(value: $distance->value))->isLessThanOrEqual(other: $maximumDistance);
    }
}
