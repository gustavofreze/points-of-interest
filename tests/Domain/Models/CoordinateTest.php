<?php

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Exceptions\NegativeCoordinate;

final class CoordinateTest extends TestCase
{
    public function testNegativeCoordinate(): void
    {
        $this->expectErrorMessage('Coordinate value <-1> cannot be negative.');
        $this->expectException(NegativeCoordinate::class);

        new Coordinate(value: -1);
    }
}
