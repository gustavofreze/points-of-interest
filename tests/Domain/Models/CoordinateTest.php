<?php

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Exceptions\NegativeCoordinate;

final class CoordinateTest extends TestCase
{
    public function testNegativeCoordinate(): void
    {
        self::expectException(NegativeCoordinate::class);
        self::expectExceptionMessage('Coordinate value <-1> cannot be negative.');

        new Coordinate(value: -1);
    }
}
