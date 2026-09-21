<?php

declare(strict_types=1);

namespace Test\Integration\Query\PointOfInterest\FindAll;

use PointsOfInterest\Query\PointOfInterest\FindAll\Http\FindPointsOfInterest;
use PointsOfInterest\Query\Shared\Http\InvalidRequest;
use Test\Integration\IntegrationTestCase;
use Test\Integration\PointOfInterestRequests;
use TinyBlocks\Http\Code;

final class FindPointsOfInterestTest extends IntegrationTestCase
{
    private const string BASE_URI = '/points-of-interest';

    private const array SEED = [
        ['name' => 'Lanchonete', 'xCoordinate' => 27, 'yCoordinate' => 12],
        ['name' => 'Posto', 'xCoordinate' => 31, 'yCoordinate' => 18],
        ['name' => 'Joalheria', 'xCoordinate' => 15, 'yCoordinate' => 12],
        ['name' => 'Floricultura', 'xCoordinate' => 19, 'yCoordinate' => 21],
        ['name' => 'Pub', 'xCoordinate' => 12, 'yCoordinate' => 8],
        ['name' => 'Supermercado', 'xCoordinate' => 23, 'yCoordinate' => 6],
        ['name' => 'Churrascaria', 'xCoordinate' => 28, 'yCoordinate' => 2]
    ];

    protected function setUp(): void
    {
        foreach (FindPointsOfInterestTest::SEED as $position => $point) {
            $this->fixtures()->insertPointOfInterest(
                id: sprintf('0193b3a1-0000-7000-8000-00000000000%d', ($position + 1)),
                name: $point['name'],
                createdAt: sprintf('2026-09-21 %02d:00:00.000000', ($position + 1)),
                xCoordinate: $point['xCoordinate'],
                yCoordinate: $point['yCoordinate']
            );
        }
    }

    public function testListsEveryPointMostRecentFirst(): void
    {
        /** @Given a request for the full listing */
        $request = PointOfInterestRequests::get(path: FindPointsOfInterestTest::BASE_URI);

        /** @When the listing is read */
        $actual = $this->get(FindPointsOfInterest::class)->handle($request);
        $body = (array)json_decode($actual->getBody()->__toString(), true);

        /** @Then the page carries every point, most recently registered first */
        self::assertSame(Code::OK->value, $actual->getStatusCode());
        self::assertSame(
            ['Churrascaria', 'Supermercado', 'Pub', 'Floricultura', 'Joalheria', 'Posto', 'Lanchonete'],
            array_column($body['data'], 'name')
        );
        self::assertFalse($body['meta']['has_next']);
    }

    public function testAnswersOnlyThePointsWithinTheMaximumDistance(): void
    {
        /** @Given the reference point and the maximum distance the challenge states */
        $path = sprintf('%s?distance=10&x_coordinate=20&y_coordinate=10', FindPointsOfInterestTest::BASE_URI);
        $request = PointOfInterestRequests::get(path: $path);

        /** @When the listing is read */
        $actual = $this->get(FindPointsOfInterest::class)->handle($request);
        $body = (array)json_decode($actual->getBody()->__toString(), true);

        /** @Then only the four points the challenge expects are answered */
        $names = array_column($body['data'], 'name');
        sort($names);

        self::assertSame(['Joalheria', 'Lanchonete', 'Pub', 'Supermercado'], $names);
    }

    public function testAnswersThePointSittingExactlyOnTheBoundary(): void
    {
        /** @Given a reference point whose distance to Supermercado is exactly five */
        $path = sprintf('%s?distance=5&x_coordinate=20&y_coordinate=10', FindPointsOfInterestTest::BASE_URI);
        $request = PointOfInterestRequests::get(path: $path);

        /** @When the listing is read */
        $actual = $this->get(FindPointsOfInterest::class)->handle($request);
        $body = (array)json_decode($actual->getBody()->__toString(), true);

        /** @Then the point at exactly that distance is included */
        self::assertSame(['Supermercado'], array_column($body['data'], 'name'));
    }

    public function testAnswersNothingWhenNoPointIsCloseEnough(): void
    {
        /** @Given a reference point with no neighbour inside the distance */
        $path = sprintf('%s?distance=1&x_coordinate=0&y_coordinate=0', FindPointsOfInterestTest::BASE_URI);
        $request = PointOfInterestRequests::get(path: $path);

        /** @When the listing is read */
        $actual = $this->get(FindPointsOfInterest::class)->handle($request);
        $body = (array)json_decode($actual->getBody()->__toString(), true);

        /** @Then the page is empty */
        self::assertSame([], $body['data']);
        self::assertFalse($body['meta']['has_next']);
    }

    public function testRefusesAProximitySearchMissingAParameter(): void
    {
        /** @Given a request carrying only part of the proximity parameters */
        $path = sprintf('%s?distance=10&x_coordinate=20', FindPointsOfInterestTest::BASE_URI);
        $request = PointOfInterestRequests::get(path: $path);

        /** @Then the request is refused naming the parameters that travel together */
        $this->expectException(InvalidRequest::class);

        /** @When the listing is read */
        try {
            $this->get(FindPointsOfInterest::class)->handle($request);
        } catch (InvalidRequest $exception) {
            self::assertSame(
                'A proximity search takes distance, x_coordinate, and y_coordinate together.',
                $exception->reason
            );
            throw $exception;
        }
    }

    public function testRendersEveryFieldOfThePublishedPoint(): void
    {
        /** @Given a request filtered down to a single point */
        $path = sprintf('%s?filter=name==Pub', FindPointsOfInterestTest::BASE_URI);
        $request = PointOfInterestRequests::get(path: $path);

        /** @When the listing is read */
        $actual = $this->get(FindPointsOfInterest::class)->handle($request);
        $body = (array)json_decode($actual->getBody()->__toString(), true);

        /** @Then the point carries its identifier, its name, its coordinates, and when it was registered */
        self::assertCount(1, $body['data']);
        self::assertSame('0193b3a1-0000-7000-8000-000000000005', $body['data'][0]['id']);
        self::assertSame('Pub', $body['data'][0]['name']);
        self::assertSame(['x_coordinate' => 12, 'y_coordinate' => 8], $body['data'][0]['point']);
        self::assertSame('2026-09-21T05:00:00.000000+00:00', $body['data'][0]['created_at']);
    }

    public function testFiltersByCoordinate(): void
    {
        /** @Given a request filtered by the x coordinate */
        $path = sprintf('%s?filter=x_coordinate=ge=27', FindPointsOfInterestTest::BASE_URI);
        $request = PointOfInterestRequests::get(path: $path);

        /** @When the listing is read */
        $actual = $this->get(FindPointsOfInterest::class)->handle($request);
        $body = (array)json_decode($actual->getBody()->__toString(), true);

        /** @Then only the points beyond that coordinate are answered */
        $names = array_column($body['data'], 'name');
        sort($names);

        self::assertSame(['Churrascaria', 'Lanchonete', 'Posto'], $names);
    }

    public function testSortsByName(): void
    {
        /** @Given a request sorted by name */
        $path = sprintf('%s?sort=name,id', FindPointsOfInterestTest::BASE_URI);
        $request = PointOfInterestRequests::get(path: $path);

        /** @When the listing is read */
        $actual = $this->get(FindPointsOfInterest::class)->handle($request);
        $body = (array)json_decode($actual->getBody()->__toString(), true);

        /** @Then the points come back in alphabetical order */
        self::assertSame(
            ['Churrascaria', 'Floricultura', 'Joalheria', 'Lanchonete', 'Posto', 'Pub', 'Supermercado'],
            array_column($body['data'], 'name')
        );
    }

    public function testCarriesTheNextCursorWhenThePageIsFull(): void
    {
        /** @Given a request for a page of one point */
        $path = sprintf('%s?page%%5Bsize%%5D=1', FindPointsOfInterestTest::BASE_URI);
        $request = PointOfInterestRequests::get(path: $path);

        /** @When the listing is read */
        $actual = $this->get(FindPointsOfInterest::class)->handle($request);
        $body = (array)json_decode($actual->getBody()->__toString(), true);

        /** @Then the page carries the next cursor and the RFC 8288 link header */
        self::assertTrue($body['meta']['has_next']);
        self::assertArrayHasKey('next', $body['links']);
        self::assertStringContainsString('rel="next"', $actual->getHeaderLine('Link'));
    }

    public function testWalksTheCursorToTheFollowingPage(): void
    {
        /** @Given the first page of one point */
        $path = sprintf('%s?page%%5Bsize%%5D=1', FindPointsOfInterestTest::BASE_URI);
        $first = $this->get(FindPointsOfInterest::class)->handle(PointOfInterestRequests::get(path: $path));
        $firstBody = (array)json_decode($first->getBody()->__toString(), true);

        /** @When the next cursor is followed */
        $second = $this->get(FindPointsOfInterest::class)->handle(
            PointOfInterestRequests::get(path: $firstBody['links']['next'])
        );
        $secondBody = (array)json_decode($second->getBody()->__toString(), true);

        /** @Then the following point is answered */
        self::assertSame('Churrascaria', $firstBody['data'][0]['name']);
        self::assertSame('Supermercado', $secondBody['data'][0]['name']);
    }

    public function testKeepsTheProximitySearchWhileWalkingTheCursor(): void
    {
        /** @Given the first page of one point inside the maximum distance */
        $path = sprintf(
            '%s?distance=10&x_coordinate=20&y_coordinate=10&page%%5Bsize%%5D=1',
            FindPointsOfInterestTest::BASE_URI
        );
        $first = $this->get(FindPointsOfInterest::class)->handle(PointOfInterestRequests::get(path: $path));
        $firstBody = (array)json_decode($first->getBody()->__toString(), true);

        /** @When the next cursor is followed */
        $second = $this->get(FindPointsOfInterest::class)->handle(
            PointOfInterestRequests::get(path: $firstBody['links']['next'])
        );
        $secondBody = (array)json_decode($second->getBody()->__toString(), true);

        /** @Then the following page stays inside the same circle */
        self::assertSame('Supermercado', $firstBody['data'][0]['name']);
        self::assertSame('Pub', $secondBody['data'][0]['name']);
    }
}
