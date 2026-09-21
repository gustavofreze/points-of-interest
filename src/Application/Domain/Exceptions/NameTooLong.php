<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Exceptions;

use DomainException;

final class NameTooLong extends DomainException
{
    public function __construct(float $current, float $maximum)
    {
        $template = 'Name is too long. Current <%d> characters, Maximum <%d> characters.';

        parent::__construct(message: sprintf($template, $current, $maximum));
    }
}
