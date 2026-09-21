<?php

declare(strict_types=1);

namespace Test\Unit\Query\PointOfInterest\FindAll;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Query\PointOfInterest\FindAll\Proximity;
use PointsOfInterest\Query\Shared\Http\InvalidRequest;

final class ProximityTest extends TestCase
{
    public static function partialParameterProvider(): array
    {
        return [
            'Only the distance'    => ['parameters' => ['distance' => '10']],
            'Only the x'           => ['parameters' => ['x_coordinate' => '20']],
            'Only the y'           => ['parameters' => ['y_coordinate' => '10']],
            'Missing the x'        => ['parameters' => ['distance' => '10', 'y_coordinate' => '10']],
            'Missing the y'        => ['parameters' => ['distance' => '10', 'x_coordinate' => '20']],
            'Missing the distance' => ['parameters' => ['x_coordinate' => '20', 'y_coordinate' => '10']]
        ];
    }

    public static function unreadableValueProvider(): array
    {
        return [
            'Negative distance'   => [
                'name'       => 'distance',
                'parameters' => ['distance' => '-1', 'x_coordinate' => '20', 'y_coordinate' => '10']
            ],
            'Fractional distance' => [
                'name'       => 'distance',
                'parameters' => ['distance' => '10.5', 'x_coordinate' => '20', 'y_coordinate' => '10']
            ],
            'Negative x'          => [
                'name'       => 'x_coordinate',
                'parameters' => ['distance' => '10', 'x_coordinate' => '-20', 'y_coordinate' => '10']
            ],
            'Word as y'           => [
                'name'       => 'y_coordinate',
                'parameters' => ['distance' => '10', 'x_coordinate' => '20', 'y_coordinate' => 'ten']
            ],
            'Array as x'          => [
                'name'       => 'x_coordinate',
                'parameters' => ['distance' => '10', 'x_coordinate' => ['20'], 'y_coordinate' => '10']
            ]
        ];
    }

    public function testIsAbsentWhenNoParameterIsGiven(): void
    {
        /** @Given a query carrying no proximity parameter */
        $parameters = ['page' => ['size' => '10']];

        /** @When the proximity is read */
        $actual = Proximity::fromQueryParameters(parameters: $parameters);

        /** @Then the search stays open */
        self::assertNull($actual);
    }

    public function testCarriesTheReferencePointAndTheDistance(): void
    {
        /** @Given a query carrying every proximity parameter */
        $parameters = ['distance' => '10', 'x_coordinate' => '20', 'y_coordinate' => '10'];

        /** @When the proximity is read */
        $actual = Proximity::fromQueryParameters(parameters: $parameters);

        /** @Then it carries the reference point and the maximum distance */
        self::assertSame(10, $actual->distance);
        self::assertSame(20, $actual->xCoordinate);
        self::assertSame(10, $actual->yCoordinate);
    }

    public function testBoundsTheSearchBoxAroundTheReferencePoint(): void
    {
        /** @Given a reference point far enough from the origin */
        $parameters = ['distance' => '10', 'x_coordinate' => '20', 'y_coordinate' => '15'];

        /** @When the proximity is read */
        $actual = Proximity::fromQueryParameters(parameters: $parameters);

        /** @Then the box spans the distance on each side and the radius is squared */
        self::assertSame(10, $actual->minimumX());
        self::assertSame(30, $actual->maximumX());
        self::assertSame(5, $actual->minimumY());
        self::assertSame(25, $actual->maximumY());
        self::assertSame(100, $actual->radiusSquared());
    }

    public function testClampsTheSearchBoxAtTheOrigin(): void
    {
        /** @Given a reference point closer to the origin than the distance */
        $parameters = ['distance' => '10', 'x_coordinate' => '3', 'y_coordinate' => '2'];

        /** @When the proximity is read */
        $actual = Proximity::fromQueryParameters(parameters: $parameters);

        /** @Then the box never reaches below the origin */
        self::assertSame(0, $actual->minimumX());
        self::assertSame(13, $actual->maximumX());
        self::assertSame(0, $actual->minimumY());
        self::assertSame(12, $actual->maximumY());
    }

    #[DataProvider('partialParameterProvider')]
    public function testRefusesAPartialProximity(array $parameters): void
    {
        /** @Given a query carrying only part of the proximity parameters */
        /** @Then the query is refused */
        $this->expectException(InvalidRequest::class);

        /** @When the proximity is read */
        Proximity::fromQueryParameters(parameters: $parameters);
    }

    #[DataProvider('unreadableValueProvider')]
    public function testRefusesAValueThatIsNotANonNegativeInteger(string $name, array $parameters): void
    {
        /** @Given a proximity parameter that is not a non-negative integer */
        /** @Then the query is refused naming the offending parameter */
        $this->expectException(InvalidRequest::class);

        /** @When the proximity is read */
        try {
            Proximity::fromQueryParameters(parameters: $parameters);
        } catch (InvalidRequest $exception) {
            self::assertSame(sprintf('The <%s> must be a non-negative integer.', $name), $exception->reason);
            throw $exception;
        }
    }
}
