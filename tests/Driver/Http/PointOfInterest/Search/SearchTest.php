<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Search;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Models\PointsOfInterest;
use PointsOfInterest\Domain\Ports\Outbound\Points;
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
        $this->points = $this->createMock(Points::class);
        $this->search = new Search(points: $this->points);
    }

    public function testInvalidRequest(): void
    {
        /** @Dado que tenho uma query string inválida */
        $query = 'x_coordinate=20&y_coordinate=10';

        /** @E que eu faça uma solicitação de listagem dos pontos de interesse usando essa query */
        $request = $this->request(query: $query);

        /** @Quando a operação de listagem for executada com essa solicitação */
        $response = $this->search->__invoke(request: $request, response: new Response());

        /** @Então um erro indicando solicitação inválida deve ser retornado */
        $message = '{"error":{"distance":"distance must be present"}}';
        self::assertEquals(HttpCode::UNPROCESSABLE_ENTITY->value, $response->getStatusCode());
        self::assertEquals($message, $response->getBody()->__toString());
    }

    public function testSearchAll(): void
    {
        /** @Dado que eu tenha pontos de interesse registrados */
        $this->addPointsOfInterest();

        /** @E que eu faça uma solicitação de listagem desses pontos de interesse */
        $request = $this->request();

        /** @Quando a operação de listagem for executada com essa solicitação */
        $response = $this->search->__invoke(request: $request, response: new Response());

        /** @Então todos os pontos de interesse registrados devem ser retornado */
        self::assertEquals(HttpCode::OK->value, $response->getStatusCode());
        self::assertCount(
            7,
            json_decode($response->getBody()->__toString(), true)
        );
    }

    public function testSearchWithFilter(): void
    {
        /** @Dado que eu tenha pontos de interesse registrados */
        $this->addPointsOfInterest();

        /** @E que tenho uma query string válida */
        $query = 'x_coordinate=20&y_coordinate=10&distance=10';

        /** @E que eu faça uma solicitação de listagem dos pontos de interesse usando essa query */
        $request = $this->request(query: $query);

        /** @Quando a operação de listagem for executada com essa solicitação */
        $response = $this->search->__invoke(request: $request, response: new Response());

        /** @Então devem ser retornados 04 pontos de interesse */
        self::assertEquals(HttpCode::OK->value, $response->getStatusCode());
        self::assertCount(
            4,
            json_decode($response->getBody()->__toString(), true)
        );
    }

    private function request(string $query = ''): SlimRequest
    {
        return RequestHttpMock::getRequest(path: '/pois', query: $query);
    }

    private function addPointsOfInterest(): void
    {
        $values = [
            PointOfInterest::from(name: 'Pub', xCoordinate: 12, yCoordinate: 8),
            PointOfInterest::from(name: 'Posto', xCoordinate: 31, yCoordinate: 18),
            PointOfInterest::from(name: 'Joalheria', xCoordinate: 15, yCoordinate: 12),
            PointOfInterest::from(name: 'Lanchonete', xCoordinate: 27, yCoordinate: 12),
            PointOfInterest::from(name: 'Churrascaria', xCoordinate: 28, yCoordinate: 2),
            PointOfInterest::from(name: 'Supermercado', xCoordinate: 23, yCoordinate: 6),
            PointOfInterest::from(name: 'Floricultura', xCoordinate: 19, yCoordinate: 21)
        ];

        $this->points->expects(self::once())
            ->method('findAll')
            ->will(self::returnValue(new PointsOfInterest(values: $values)));
    }
}
