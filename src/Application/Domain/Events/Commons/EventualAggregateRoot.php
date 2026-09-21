<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Events\Commons;

use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot as TinyBlocksEventualAggregateRoot;

/**
 * Aggregate root that records the facts of its own transitions.
 */
interface EventualAggregateRoot extends TinyBlocksEventualAggregateRoot
{
}
