<?php

declare(strict_types=1);

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Exceptions\NegativeCoordinate;

final class CoordinateTest extends TestCase
{
    #[DataProvider('coordinateValuesDataProvider')]
    public function testValidCoordinate(int $value): void
    {
        /** @Given a valid value for the coordinate is provided */
        /** @When the Coordinate is instantiated with the valid value */
        $coordinate = new Coordinate(value: $value);

        /** @Then the Coordinate should be created successfully with the correct value */
        self::assertInstanceOf(Coordinate::class, $coordinate);
        self::assertSame($value, $coordinate->value);
    }

    public function testSquareRootRounding(): void
    {
        /** @Given a coordinate with a non-perfect square value */
        $coordinate = new Coordinate(value: 10);

        /** @When the square root is calculated */
        $result = $coordinate->squareRoot();

        /** @Then the square root should be rounded to the nearest integer */
        self::assertSame(3, $result->value);

        /** @Given a coordinate where rounding would go up */
        $coordinate = new Coordinate(value: 15);

        /** @When the square root is calculated */
        $result = $coordinate->squareRoot();

        /** @Then the square root should be rounded to the nearest integer */
        self::assertSame(4, $result->value);
    }

    public function testNegativeCoordinate(): void
    {
        /** @Given a negative value is provided for the coordinate */
        $negativeValue = -1;

        /** @Then a NegativeCoordinate exception should be thrown */
        self::expectException(NegativeCoordinate::class);
        self::expectExceptionMessage('Coordinate value <-1> cannot be negative.');

        /** @When attempting to instantiate a Coordinate with the negative value */
        new Coordinate(value: $negativeValue);
    }

    public static function coordinateValuesDataProvider(): array
    {
        return [
            'Zero coordinate'           => ['value' => 0],
            'Positive coordinate'       => ['value' => 10],
            'Large positive coordinate' => ['value' => 1000]
        ];
    }
}
