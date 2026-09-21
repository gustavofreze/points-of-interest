<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointOfInterest\Outbox\Event;

use TinyBlocks\BuildingBlocks\Event\IntegrationEvent;

/**
 * Fact a point of interest publishes for consumers outside the points of interest domain.
 */
interface PointOfInterestIntegrationEvent extends IntegrationEvent
{
}
