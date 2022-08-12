<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Register;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Boundaries\Points;
use PointsOfInterest\Driver\Http\PointOfInterest\Register\Dtos\Request;
use PointsOfInterest\Mock\RequestHttpMock;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response;
use TinyBlocks\Http\HttpCode;

final class RegisterTest extends TestCase
{
    private Points $points;
    private Register $register;

    protected function setUp(): void
    {
        $this->points = $this->createMock(Points::class);
        $this->register = new Register(points: $this->points);
    }

    public function testInvalidRequest(): void
    {
        /** @Dado que tenho um ponto de interesse inválido */
        $body = ['point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @E que eu faça uma solicitação de registro com os dados desse ponto de interesse */
        $request = $this->request(body: $body);

        /** @Quando a operação de registrar for executada com essa solicitação */
        $response = $this->register->__invoke(request: $request, response: new Response());

        /** @Então um erro indicando solicitação inválida deve ser retornado */
        $message = '{"error":{"name":"name must be present"}}';
        self::assertEquals(HttpCode::UNPROCESSABLE_ENTITY->value, $response->getStatusCode());
        self::assertEquals($message, $response->getBody()->__toString());
    }

    public function testPointOfInterestAlreadyExists(): void
    {
        /** @Dado que tenho um ponto de interesse válido */
        $body = ['name' => 'xpto', 'point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @E que esse ponto de interesse já tenha sido registrado anteriormente */
        $this->addPointOfInterest(body: $body);

        /** @E que eu faça uma solicitação de registro com os dados desse ponto de interesse */
        $request = $this->request(body: $body);

        /** @Quando a operação de registrar for executada com essa solicitação */
        $response = $this->register->__invoke(request: $request, response: new Response());

        /** @Então um erro indicando que o ponto já existe deve ser retornado */
        $template = 'A point of interest with name <%s>, x coordinate <%s> and y coordinate <%s> already exists.';
        $message = json_encode([
            'error' => sprintf(
                $template,
                $body['name'],
                $body['point']['x_coordinate'],
                $body['point']['y_coordinate']
            )
        ]);
        self::assertEquals(HttpCode::CONFLICT->value, $response->getStatusCode());
        self::assertEquals($message, $response->getBody()->__toString());
    }

    public function testRegisterPointOfInterest(): void
    {
        /** @Dado que tenho um ponto de interesse válido */
        $body = ['name' => 'xpto', 'point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @E que eu faça uma solicitação de registro com os dados desse ponto de interesse */
        $request = $this->request(body: $body);

        /** @Quando a operação de registrar for executada com essa solicitação */
        $response = $this->register->__invoke(request: $request, response: new Response());

        /** @Então o registro do ponto deve ocorrer com sucesso */
        self::assertEquals(HttpCode::CREATED->value, $response->getStatusCode());
        self::assertEquals($body, json_decode($response->getBody()->__toString(), true));
    }

    private function request(array $body): SlimRequest
    {
        return RequestHttpMock::postRequest(path: '/pois', body: json_encode($body));
    }

    private function addPointOfInterest(array $body): void
    {
        $request = new Request(request: $body);
        $pointOfInterest = $request->toPointOfInterest();

        $this->points->expects(self::once())
            ->method('find')
            ->with($pointOfInterest)
            ->will(self::returnValue($pointOfInterest));
    }
}
