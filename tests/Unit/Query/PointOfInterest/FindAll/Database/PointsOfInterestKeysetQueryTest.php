<?php

declare(strict_types=1);

namespace Test\Unit\Query\PointOfInterest\FindAll\Database;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Query\PointOfInterest\FindAll\Database\PointsOfInterestKeysetQuery;
use PointsOfInterest\Query\PointOfInterest\FindAll\Http\FindPointsOfInterestRequest;
use Test\Unit\RequestFactory;

final class PointsOfInterestKeysetQueryTest extends TestCase
{
    public function testSelectsEveryPointWhenNothingNarrowsTheSearch(): void
    {
        /** @Given a request carrying no filter and no proximity */
        $query = FindPointsOfInterestRequest::from(request: RequestFactory::getWith(queryParameters: []));

        /** @When the statement is built */
        $actual = PointsOfInterestKeysetQuery::from(
            keyset: $query->keyset,
            proximity: $query->proximity,
            comparisons: $query->comparisons
        );

        /** @Then it carries no predicate and orders by the default sort */
        self::assertStringNotContainsString('WHERE', $actual->sql);
        self::assertStringContainsString('ORDER BY poi.created_at DESC, poi.id DESC', $actual->sql);
        self::assertSame([], $actual->parameters);
    }

    public function testBoundsTheSearchByTheBoxAndTheCircle(): void
    {
        /** @Given a request carrying a proximity search */
        $query = FindPointsOfInterestRequest::from(request: RequestFactory::getWith(queryParameters: [
            'distance'     => '10',
            'x_coordinate' => '20',
            'y_coordinate' => '10'
        ]));

        /** @When the statement is built */
        $actual = PointsOfInterestKeysetQuery::from(
            keyset: $query->keyset,
            proximity: $query->proximity,
            comparisons: $query->comparisons
        );

        /** @Then the box narrows the scan and the circle refines it */
        self::assertStringContainsString(
            'poi.x_coordinate BETWEEN :proximityMinimumX AND :proximityMaximumX',
            $actual->sql
        );
        self::assertStringContainsString(
            'poi.y_coordinate BETWEEN :proximityMinimumY AND :proximityMaximumY',
            $actual->sql
        );
        self::assertStringContainsString('<= :proximityRadiusSquared', $actual->sql);
        self::assertSame(
            [
                'proximityX'             => 20,
                'proximityY'             => 10,
                'proximityMinimumX'      => 10,
                'proximityMaximumX'      => 30,
                'proximityMinimumY'      => 0,
                'proximityMaximumY'      => 20,
                'proximityRadiusSquared' => 100
            ],
            $actual->parameters
        );
    }

    public function testNarrowsTheSearchByTheFilterExpression(): void
    {
        /** @Given a request carrying a filter on the name */
        $query = FindPointsOfInterestRequest::from(request: RequestFactory::getWith(queryParameters: [
            'filter' => 'name==Pub'
        ]));

        /** @When the statement is built */
        $actual = PointsOfInterestKeysetQuery::from(
            keyset: $query->keyset,
            proximity: $query->proximity,
            comparisons: $query->comparisons
        );

        /** @Then the name column is compared and its value is bound */
        self::assertStringContainsString('WHERE', $actual->sql);
        self::assertStringContainsString('poi.name', $actual->sql);
        self::assertContains('Pub', $actual->parameters);
    }

    public function testCapsThePageAtOneRowBeyondTheRequestedSize(): void
    {
        /** @Given a request asking for a page of three */
        $query = FindPointsOfInterestRequest::from(request: RequestFactory::getWith(queryParameters: [
            'page' => ['size' => '3']
        ]));

        /** @When the statement is built */
        $actual = PointsOfInterestKeysetQuery::from(
            keyset: $query->keyset,
            proximity: $query->proximity,
            comparisons: $query->comparisons
        );

        /** @Then it reads one extra row to know whether another page follows */
        self::assertStringContainsString('LIMIT 4', $actual->sql);
    }
}
