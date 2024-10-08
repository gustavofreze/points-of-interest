<?php

declare(strict_types=1);

namespace PointsOfInterest\Domain\Models;

use PointsOfInterest\Domain\Exceptions\NameCannotBeEmpty;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class Name implements ValueObject
{
    use ValueObjectBehavior;

    public function __construct(public string $value)
    {
        if (empty($this->value)) {
            throw new NameCannotBeEmpty();
        }
    }
}
