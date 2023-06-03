<?php

namespace PointsOfInterest\Domain\Ports\Outbound;

use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;

interface Points
{
    public function save(PointOfInterest $pointOfInterest): void;

    public function find(PointOfInterest $pointOfInterest): ?PointOfInterest;

    public function findAll(): PointsOfInterest;
}
