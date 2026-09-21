<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Events;

use PointsOfInterest\Application\Domain\Events\Commons\DomainEvent;

/**
 * Fact recorded by the point of interest aggregate when it moves.
 */
interface PointOfInterestEvent extends DomainEvent
{
}
