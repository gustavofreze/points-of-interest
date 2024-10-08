<?php

declare(strict_types=1);

namespace PointsOfInterest\Domain\Models;

use PointsOfInterest\Domain\Exceptions\NegativeCoordinate;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class Coordinate implements ValueObject
{
    use ValueObjectBehavior;

    public function __construct(public int $value)
    {
        if ($this->value < 0) {
            throw new NegativeCoordinate(value: $this->value);
        }
    }

    public function add(Coordinate $addend): Coordinate
    {
        return new Coordinate(value: $this->value + $addend->value);
    }

    public function subtract(Coordinate $subtrahend): Coordinate
    {
        return new Coordinate(value: abs($this->value - $subtrahend->value));
    }

    public function squared(): Coordinate
    {
        return new Coordinate(value: pow($this->value, 2));
    }

    public function squareRoot(): Coordinate
    {
        return new Coordinate(value: (int)round(sqrt($this->value)));
    }
}
