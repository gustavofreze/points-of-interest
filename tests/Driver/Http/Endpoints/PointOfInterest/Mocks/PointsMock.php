<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks;

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
        return $this->pointOfInterest[0] ?? null;
    }

    public function findAll(): PointsOfInterest
    {
        return PointsOfInterest::createFrom(elements: $this->pointOfInterest);
    }
}
