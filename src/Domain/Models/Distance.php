<?php

declare(strict_types=1);

namespace PointsOfInterest\Domain\Models;

use PointsOfInterest\Domain\Exceptions\NegativeDistance;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class Distance implements ValueObject
{
    use ValueObjectBehavior;

    public function __construct(public int $value)
    {
        if ($this->value < 0) {
            throw new NegativeDistance(value: $this->value);
        }
    }

    public function isLessThanOrEqual(Distance $other): bool
    {
        return $this->value <= $other->value;
    }
}
