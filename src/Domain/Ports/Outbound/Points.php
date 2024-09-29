<?php

namespace PointsOfInterest\Domain\Ports\Outbound;

use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;

interface Points
{
    /**
     * Save a point of interest.
     *
     * @param PointOfInterest $pointOfInterest The point of interest to save.
     * @return void
     */
    public function save(PointOfInterest $pointOfInterest): void;

    /**
     * Find a point of interest.
     *
     * @param PointOfInterest $pointOfInterest The point of interest to find.
     * @return PointOfInterest|null Returns the point of interest if found, or null if not found.
     */
    public function find(PointOfInterest $pointOfInterest): ?PointOfInterest;

    /**
     * Find all points of interest.
     *
     * @return PointsOfInterest A collection of all points of interest.
     */
    public function findAll(): PointsOfInterest;
}
