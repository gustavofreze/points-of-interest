<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Ports\Outbound;

use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterest;

/**
 * Points of interest recorded by the service.
 */
interface PointsOfInterest
{
    /**
     * Records the point of interest together with the facts it produced.
     *
     * @param PointOfInterest $pointOfInterest The point of interest that was registered.
     */
    public function save(PointOfInterest $pointOfInterest): void;
}
