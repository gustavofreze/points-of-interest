<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Commands;

use PointsOfInterest\Application\Domain\Models\Commons\Name;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\Coordinates;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterestId;

final readonly class RegisterPointOfInterest implements Command
{
    public function __construct(public PointOfInterestId $id, public Name $name, public Coordinates $coordinates)
    {
    }
}
