<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Events\Commons;

use TinyBlocks\BuildingBlocks\Event\DomainEvent as TinyBlocksDomainEvent;

/**
 * Domain event emitted by an aggregate root within the points of interest domain.
 */
interface DomainEvent extends TinyBlocksDomainEvent
{
}
