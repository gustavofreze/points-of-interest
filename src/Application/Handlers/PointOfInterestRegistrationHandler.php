<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Handlers;

use PointsOfInterest\Application\Commands\RegisterPointOfInterest;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterest;
use PointsOfInterest\Application\Ports\Inbound\PointOfInterestRegistration;
use PointsOfInterest\Application\Ports\Outbound\PointsOfInterest;

final readonly class PointOfInterestRegistrationHandler implements PointOfInterestRegistration
{
    public function __construct(private PointsOfInterest $pointsOfInterest)
    {
    }

    public function handle(RegisterPointOfInterest $command): void
    {
        $pointOfInterest = PointOfInterest::register(
            id: $command->id,
            name: $command->name,
            coordinates: $command->coordinates
        );

        $this->pointsOfInterest->save(pointOfInterest: $pointOfInterest);
    }
}
