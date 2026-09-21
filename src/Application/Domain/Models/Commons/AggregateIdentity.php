<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Models\Commons;

use TinyBlocks\BuildingBlocks\Entity\Identity as TinyBlocksIdentity;

/**
 * Identity of an aggregate root within the points of interest domain.
 */
interface AggregateIdentity extends TinyBlocksIdentity
{
}
