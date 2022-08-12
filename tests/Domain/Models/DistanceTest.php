<?php

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Exceptions\NegativeDistance;

final class DistanceTest extends TestCase
{
    public function testNegativeDistance(): void
    {
        $this->expectErrorMessage('The distance value <-1> must be a non-negative integer.');
        $this->expectException(NegativeDistance::class);

        new Distance(value: -1);
    }
}
