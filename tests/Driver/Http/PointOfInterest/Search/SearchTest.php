<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Search;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;
use PointsOfInterest\Domain\Ports\Outbound\Points;
use PointsOfInterest\Driver\Http\PointOfInterest\Search\Mocks\PointsMock;
use PointsOfInterest\Mock\RequestHttpMock;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response;
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

    public function testInvalidRequest(): void
    {
        /** @Given I have an invalid query string */
        $query = 'x_coordinate=20&y_coordinate=10';

        /** @When I send a request to list points of interest with this invalid query */
        $request = $this->request(query: $query);
        $response = $this->search->__invoke(request: $request, response: new Response());

        /** @Then an error for invalid request should be returned */
        $message = '{"error":{"distance":"distance must be present"}}';
        self::assertEquals(HttpCode::UNPROCESSABLE_ENTITY->value, $response->getStatusCode());
        self::assertEquals($message, $response->getBody()->__toString());
    }

    public function testSearchAll(): void
    {
        /** @Given I have registered several points of interest */
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

        /** @When I send a request to list all points of interest */
        $request = $this->request();
        $response = $this->search->__invoke(request: $request, response: new Response());

        /** @Then all registered points of interest should be returned */
        self::assertEquals(HttpCode::OK->value, $response->getStatusCode());
        self::assertCount(7, json_decode($response->getBody()->__toString(), true));
    }

    public function testSearchWithFilter(): void
    {
        /** @Given I have registered several points of interest */
        /** @Given I have registered several points of interest */
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

        /** @And I have a valid query string for filtering points */
        $query = 'x_coordinate=20&y_coordinate=10&distance=10';

        /** @When I send a request to list filtered points of interest */
        $request = $this->request(query: $query);
        $response = $this->search->__invoke(request: $request, response: new Response());

        /** @Then 4 points of interest should be returned */
        self::assertEquals(HttpCode::OK->value, $response->getStatusCode());
        self::assertCount(4, json_decode($response->getBody()->__toString(), true));
    }

    private function request(string $query = ''): SlimRequest
    {
        return RequestHttpMock::getRequest(path: '/pois', query: $query);
    }
}
