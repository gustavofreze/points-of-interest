<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Exceptions;

use DomainException;

final class CoordinateOutOfRange extends DomainException
{
    public function __construct(int $current, int $maximum)
    {
        $template = 'Coordinate is out of range. Current <%d>, Minimum <0>, Maximum <%d>.';

        parent::__construct(message: sprintf($template, $current, $maximum));
    }
}
