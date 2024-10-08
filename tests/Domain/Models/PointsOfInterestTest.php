<?php

declare(strict_types=1);

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PointsOfInterestTest extends TestCase
{
    #[DataProvider('providerForTestByProximity')]
    public function testByProximity(PointsOfInterest $points, PointsOfInterest $expected): void
    {
        /** @Given that I have a reference point indicated by the GPS receiver */
        $referencePoint = ReferencePoint::from(xCoordinate: 20, yCoordinate: 10);

        /** @And that I have a maximum distance of 10 meters */
        $maximumDistance = new Distance(value: 10);

        /** @When the byProximity method is executed */
        $actual = $points->byProximity(referencePoint: $referencePoint, maximumDistance: $maximumDistance);

        /** @Then four points of interest should be returned */
        self::assertCount(4, $actual);

        /** @And the points returned should exactly match the expected list of points */
        self::assertTrue($expected->equals(other: $actual));
    }

    #[DataProvider('providerForTestByExactProximity')]
    public function testByExactProximity(PointsOfInterest $points, PointsOfInterest $expected): void
    {
        /** @Given that I have a reference point indicated by the GPS receiver */
        $referencePoint = ReferencePoint::from(xCoordinate: 0, yCoordinate: 9999);

        /** @And that I have a maximum distance of 0 meters */
        $maximumDistance = new Distance(value: 0);

        /** @When the byProximity method is executed */
        $actual = $points->byProximity(referencePoint: $referencePoint, maximumDistance: $maximumDistance);

        /** @Then only one point of interest should be returned */
        self::assertCount(1, $actual);

        /** @And the points returned should exactly match the expected list of points */
        self::assertTrue($expected->equals(other: $actual));
    }

    public static function providerForTestByProximity(): iterable
    {
        $expected = [
            PointOfInterest::from(name: 'Pub', xCoordinate: 12, yCoordinate: 8),
            PointOfInterest::from(name: 'Joalheria', xCoordinate: 15, yCoordinate: 12),
            PointOfInterest::from(name: 'Lanchonete', xCoordinate: 27, yCoordinate: 12),
            PointOfInterest::from(name: 'Supermercado', xCoordinate: 23, yCoordinate: 6)
        ];

        $points = PointsOfInterest::createFrom(elements: array_merge([
            PointOfInterest::from(name: 'Posto', xCoordinate: 31, yCoordinate: 18),
            PointOfInterest::from(name: 'Churrascaria', xCoordinate: 28, yCoordinate: 2),
            PointOfInterest::from(name: 'Floricultura', xCoordinate: 19, yCoordinate: 21)
        ], $expected));

        $expectedPoints = PointsOfInterest::createFrom(elements: $expected);

        yield [
            'points'   => $points,
            'expected' => $expectedPoints
        ];
    }

    public static function providerForTestByExactProximity(): iterable
    {
        $expected = [
            PointOfInterest::from(name: 'Academia', xCoordinate: 0, yCoordinate: 9999)
        ];

        $points = PointsOfInterest::createFrom(elements: array_merge([
            PointOfInterest::from(name: 'Posto', xCoordinate: 31, yCoordinate: 18),
            PointOfInterest::from(name: 'Joalheria', xCoordinate: 15, yCoordinate: 12),
            PointOfInterest::from(name: 'Lanchonete', xCoordinate: 27, yCoordinate: 12),
            PointOfInterest::from(name: 'Churrascaria', xCoordinate: 28, yCoordinate: 2)
        ], $expected));

        $expectedPoints = PointsOfInterest::createFrom(elements: $expected);

        yield [
            'points'   => $points,
            'expected' => $expectedPoints
        ];
    }
}
