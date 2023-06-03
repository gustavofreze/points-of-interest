<?php

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Exceptions\NegativeDistance;

final class DistanceTest extends TestCase
{
    public function testNegativeDistance(): void
    {
        self::expectException(NegativeDistance::class);
        self::expectExceptionMessage('The distance value <-1> must be a non-negative integer.');

        new Distance(value: -1);
    }
}
