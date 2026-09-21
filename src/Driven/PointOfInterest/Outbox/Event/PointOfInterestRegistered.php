<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointOfInterest\Outbox\Event;

use TinyBlocks\BuildingBlocks\Event\IntegrationEventBehavior;

final readonly class PointOfInterestRegistered implements PointOfInterestIntegrationEvent
{
    use IntegrationEventBehavior;

    public function __construct(public string $name, public int $xCoordinate, public int $yCoordinate)
    {
    }
}
