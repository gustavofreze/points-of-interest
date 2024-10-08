<?php


declare(strict_types=1);

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Exceptions\NegativeDistance;

final class DistanceTest extends TestCase
{
    public function testNegativeDistance(): void
    {
        /** @Given a negative value is provided for the distance */
        $negativeValue = -1;

        /** @Then a NegativeDistance exception should be thrown */
        self::expectException(NegativeDistance::class);
        self::expectExceptionMessage('The distance value <-1> must be a non-negative integer.');

        /** @When attempting to instantiate a Distance with the negative value */
        new Distance(value: $negativeValue);
    }

    #[DataProvider('distanceValuesDataProvider')]
    public function testValidDistance(int $value): void
    {
        /** @Given a valid value for the distance is provided */
        /** @When the Distance is instantiated with the valid value */
        $distance = new Distance(value: $value);

        /** @Then the Distance should be created successfully with the correct value */
        self::assertInstanceOf(Distance::class, $distance);
        self::assertSame($value, $distance->value);
    }

    public static function distanceValuesDataProvider(): array
    {
        return [
            'Zero distance'           => ['value' => 0],
            'Positive distance'       => ['value' => 10],
            'Large positive distance' => ['value' => 1000]
        ];
    }
}
