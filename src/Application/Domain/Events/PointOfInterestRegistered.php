<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Events;

use PointsOfInterest\Application\Domain\Events\Commons\DomainEventBehavior;
use PointsOfInterest\Application\Domain\Models\Commons\Name;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\Coordinates;

final readonly class PointOfInterestRegistered implements PointOfInterestEvent
{
    use DomainEventBehavior;

    public function __construct(public Name $name, public Coordinates $coordinates)
    {
    }

    public function eventType(): string
    {
        return 'PointOfInterestRegistered';
    }
}
