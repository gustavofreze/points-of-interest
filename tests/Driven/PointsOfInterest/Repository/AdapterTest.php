<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointsOfInterest\Repository;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Ports\Outbound\Points;
use PointsOfInterest\Driven\PointsOfInterest\Repository\Mocks\ConnectionMock;

final class AdapterTest extends TestCase
{
    private Points $points;
    private ConnectionMock $connection;

    protected function setUp(): void
    {
        $this->connection = new ConnectionMock();
        $this->points = new Adapter(connection: $this->connection);
    }

    public function testSave(): void
    {
        /** @Given I have a valid point of interest */
        $pointOfInterest = PointOfInterest::from(
            name: 'Pub',
            xCoordinate: rand(1, 1000),
            yCoordinate: rand(1, 1000)
        );

        /** @When the persistence of this point of interest is requested */
        $this->points->save(pointOfInterest: $pointOfInterest);

        /** @Then the point of interest should be inserted */
        $actual = $this->connection->lastInserted();
        self::assertNotEmpty($actual);

        /** @And the inserted data should match the point of interest */
        $expected = [
            'name'        => $pointOfInterest->name->value,
            'xCoordinate' => $pointOfInterest->xCoordinate->value,
            'yCoordinate' => $pointOfInterest->yCoordinate->value,
        ];
        self::assertSame($expected, $actual);
    }

    public function testFindReturningNull(): void
    {
        /** @Given I have a point of interest that is not saved in the database */
        $pointOfInterest = PointOfInterest::from(
            name: 'Nonexistent Pub',
            xCoordinate: 999,
            yCoordinate: 999
        );

        /** @When this point is requested */
        $actual = $this->points->find(pointOfInterest: $pointOfInterest);

        /** @Then no point of interest should be returned */
        self::assertNull($actual);
    }

    public function testFindAllReturningEmpty(): void
    {
        /** @Given no points of interest are saved in the database */
        $this->connection->deleteAll();

        /** @When all points of interest are requested */
        $actual = $this->points->findAll();

        /** @Then no points of interest should be returned */
        self::assertTrue($actual->isEmpty());
    }

    public function testFindReturningPointOfInterest(): void
    {
        /** @Given I have a point of interest saved in the database */
        $pointOfInterest = PointOfInterest::from(name: 'Pub', xCoordinate: 12, yCoordinate: 34);

        /** @And I have inserted points */
        $this->points->save(pointOfInterest: $pointOfInterest);

        /** @When this point is requested */
        $actual = $this->points->find(pointOfInterest: $pointOfInterest);

        /** @Then the point of interest should be returned */
        self::assertNotNull($actual);

        /** @And the returned point of interest should match the saved point */
        self::assertSame($pointOfInterest->name->value, $actual->name->value);
        self::assertSame($pointOfInterest->xCoordinate->value, $actual->xCoordinate->value);
        self::assertSame($pointOfInterest->yCoordinate->value, $actual->yCoordinate->value);
    }

    public function testFindAllReturningPointsOfInterest(): void
    {
        /** @Given I have multiple points of interest saved in the database */
        $pointsOfInterest = [
            PointOfInterest::from(name: 'Pub', xCoordinate: 12, yCoordinate: 34),
            PointOfInterest::from(name: 'Churrascaria', xCoordinate: 56, yCoordinate: 78)
        ];

        $this->points->save(pointOfInterest: $pointsOfInterest[0]);
        $this->points->save(pointOfInterest: $pointsOfInterest[1]);

        /** @When all points of interest are requested */
        $actual = $this->points->findAll();

        /** @Then the points of interest should be returned */
        self::assertCount(2, $actual);

        /** @And the returned points of interest should match the saved points */
        $expected = [
            [
                'name'        => ['value' => 'Pub'],
                'xCoordinate' => ['value' => 12],
                'yCoordinate' => ['value' => 34]
            ],
            [
                'name'        => ['value' => 'Churrascaria'],
                'xCoordinate' => ['value' => 56],
                'yCoordinate' => ['value' => 78]
            ]
        ];

        self::assertSame($expected, $actual->toArray());
    }
}
