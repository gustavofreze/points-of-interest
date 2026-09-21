<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Events\Commons;

use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior as TinyBlocksEventualAggregateRootBehavior;

trait EventualAggregateRootBehavior
{
    use TinyBlocksEventualAggregateRootBehavior;
}
