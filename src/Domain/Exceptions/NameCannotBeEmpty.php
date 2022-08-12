<?php

namespace PointsOfInterest\Domain\Exceptions;

use DomainException;

final class NameCannotBeEmpty extends DomainException
{
    public function __construct()
    {
        parent::__construct(message: 'The name cannot be empty.');
    }
}
