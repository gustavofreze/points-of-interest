<?php


declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;
use PointsOfInterest\Domain\Ports\Outbound\Points;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks\PointsMock;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks\RequestHttpMock;
use TinyBlocks\Http\HttpCode;

final class SearchTest extends TestCase
{
    private Points $points;
    private Search $search;

    protected function setUp(): void
    {
        $this->points = new PointsMock();
        $this->search = new Search(points: $this->points);
    }

    public function testSearchAll(): void
    {
        /** @Given several points of interest are registered in the system */
        $points = PointsOfInterest::createFrom(elements: [
            PointOfInterest::from(name: 'Pub', xCoordinate: 12, yCoordinate: 8),
            PointOfInterest::from(name: 'Posto', xCoordinate: 31, yCoordinate: 18),
            PointOfInterest::from(name: 'Joalheria', xCoordinate: 15, yCoordinate: 12),
            PointOfInterest::from(name: 'Lanchonete', xCoordinate: 27, yCoordinate: 12),
            PointOfInterest::from(name: 'Churrascaria', xCoordinate: 28, yCoordinate: 2),
            PointOfInterest::from(name: 'Supermercado', xCoordinate: 23, yCoordinate: 6),
            PointOfInterest::from(name: 'Floricultura', xCoordinate: 19, yCoordinate: 21)
        ]);
        $points->each(fn(PointOfInterest $pointOfInterest) => $this->points->save(pointOfInterest: $pointOfInterest));

        /** @When a request is made to retrieve all points of interest */
        $response = $this->search->handle(request: RequestHttpMock::getFrom(path: '/pois'));

        /** @Then the response should contain all registered points of interest */
        self::assertSame(HttpCode::OK->value, $response->getStatusCode());
        self::assertCount(7, json_decode($response->getBody()->__toString(), true));
    }

    public function testSearchWithFilter(): void
    {
        /** @Given several points of interest are registered in the system */
        $points = PointsOfInterest::createFrom(elements: [
            PointOfInterest::from(name: 'Pub', xCoordinate: 12, yCoordinate: 8),
            PointOfInterest::from(name: 'Posto', xCoordinate: 31, yCoordinate: 18),
            PointOfInterest::from(name: 'Joalheria', xCoordinate: 15, yCoordinate: 12),
            PointOfInterest::from(name: 'Lanchonete', xCoordinate: 27, yCoordinate: 12),
            PointOfInterest::from(name: 'Churrascaria', xCoordinate: 28, yCoordinate: 2),
            PointOfInterest::from(name: 'Supermercado', xCoordinate: 23, yCoordinate: 6),
            PointOfInterest::from(name: 'Floricultura', xCoordinate: 19, yCoordinate: 21)
        ]);
        $points->each(fn(PointOfInterest $pointOfInterest) => $this->points->save(pointOfInterest: $pointOfInterest));

        /** @And a valid query string is provided to filter points of interest */
        $query = 'x_coordinate=20&y_coordinate=10&distance=10';

        /** @When a request is made to retrieve filtered points of interest */
        $response = $this->search->handle(request: RequestHttpMock::getFrom(path: '/pois', query: $query));

        /** @Then the response should contain only the points that match the filter criteria */
        $actual = json_decode($response->getBody()->__toString(), true);

        $expected = PointsOfInterest::createFrom(elements: [
            ['name' => 'Pub', 'point' => ['x_coordinate' => 12, 'y_coordinate' => 8]],
            ['name' => 'Joalheria', 'point' => ['x_coordinate' => 15, 'y_coordinate' => 12]],
            ['name' => 'Lanchonete', 'point' => ['x_coordinate' => 27, 'y_coordinate' => 12]],
            ['name' => 'Supermercado', 'point' => ['x_coordinate' => 23, 'y_coordinate' => 6]]
        ]);

        self::assertSame(HttpCode::OK->value, $response->getStatusCode());
        self::assertSame($expected->toArray(), $actual);
        self::assertCount(4, $actual);
    }
}
