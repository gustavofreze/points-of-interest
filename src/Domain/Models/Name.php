<?php

namespace PointsOfInterest\Domain\Models;

use PointsOfInterest\Domain\Exceptions\NameCannotBeEmpty;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectAdapter;

final readonly class Name implements ValueObject
{
    use ValueObjectAdapter;

    public function __construct(public string $value)
    {
        if (empty($this->value)) {
            throw new NameCannotBeEmpty();
        }
    }
}
