<?php

declare(strict_types=1);

namespace PointsOfInterest\Domain\Exceptions;

use DomainException;

final class NegativeDistance extends DomainException
{
    public function __construct(int $value)
    {
        $template = 'The distance value <%s> must be a non-negative integer.';
        parent::__construct(message: sprintf($template, $value));
    }
}
