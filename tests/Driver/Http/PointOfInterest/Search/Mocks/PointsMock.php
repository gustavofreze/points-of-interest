<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Search\Mocks;

use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;
use PointsOfInterest\Domain\Ports\Outbound\Points;

final class PointsMock implements Points
{
    private array $pointOfInterest = [];

    public function save(PointOfInterest $pointOfInterest): void
    {
        $this->pointOfInterest[] = $pointOfInterest;
    }

    public function find(PointOfInterest $pointOfInterest): ?PointOfInterest
    {
        return null;
    }

    public function findAll(): PointsOfInterest
    {
        return PointsOfInterest::createFrom(elements: $this->pointOfInterest);
    }
}
