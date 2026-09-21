<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Ports\Inbound;

use PointsOfInterest\Application\Commands\RegisterPointOfInterest;

/**
 * Registers a point of interest at the coordinates a GPS receiver reported for it.
 */
interface PointOfInterestRegistration
{
    /**
     * Registers the point of interest the command carries.
     *
     * @param RegisterPointOfInterest $command The name and the coordinates the point of interest sits at.
     */
    public function handle(RegisterPointOfInterest $command): void;
}
