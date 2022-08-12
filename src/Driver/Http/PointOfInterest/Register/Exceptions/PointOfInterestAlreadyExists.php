<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Register\Exceptions;

use PointsOfInterest\Domain\Models\PointOfInterest;
use RuntimeException;
use TinyBlocks\Http\HttpCode;

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
            code: HttpCode::CONFLICT->value
        );
    }
}
