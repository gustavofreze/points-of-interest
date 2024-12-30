<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\Exceptions;

use PointsOfInterest\Domain\Models\PointOfInterest;
use RuntimeException;
use TinyBlocks\Http\Code;

final class PointOfInterestAlreadyExists extends RuntimeException
{
    public function __construct(PointOfInterest $pointOfInterest)
    {
        $template = 'A point of interest with name <%s>, x coordinate <%s> and y coordinate <%s> already exists.';
        parent::__construct(
            message: sprintf(
                $template,
                $pointOfInterest->name->value,
                $pointOfInterest->xCoordinate->value,
                $pointOfInterest->yCoordinate->value
            ),
            code: Code::CONFLICT->value
        );
    }
}
