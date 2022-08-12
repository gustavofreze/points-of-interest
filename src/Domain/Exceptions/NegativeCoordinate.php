<?php

namespace PointsOfInterest\Domain\Exceptions;

use DomainException;

final class NegativeCoordinate extends DomainException
{
    public function __construct(int $value)
    {
        $template = 'Coordinate value <%s> cannot be negative.';
        parent::__construct(message: sprintf($template, $value));
    }
}
