<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Register;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Ports\Outbound\Points;
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
        /** @Given that I have an invalid point of interest */
        $body = ['point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @And that I make a registration request with the data of this point of interest */
        $request = $this->request(body: $body);

        /** @When the register operation is executed with this request */
        $response = $this->register->__invoke(request: $request, response: new Response());

        /** @Then an error indicating an invalid request should be returned */
        $message = '{"error":{"name":"name must be present"}}';
        self::assertEquals(HttpCode::UNPROCESSABLE_ENTITY->value, $response->getStatusCode());
        self::assertEquals($message, $response->getBody()->__toString());
    }

    public function testPointOfInterestAlreadyExists(): void
    {
        /** @Given that I have a valid point of interest */
        $body = ['name' => 'xpto', 'point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @And that this point of interest has already been registered before */
        $this->addPointOfInterest(body: $body);

        /** @And that I make a registration request with the data of this point of interest */
        $request = $this->request(body: $body);

        /** @When the register operation is executed with this request */
        $response = $this->register->__invoke(request: $request, response: new Response());

        /** @Then an error indicating that the point already exists should be returned */
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
        /** @Given that I have a valid point of interest */
        $body = ['name' => 'xpto', 'point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @And that I make a registration request with the data of this point of interest */
        $request = $this->request(body: $body);

        /** @When the register operation is executed with this request */
        $response = $this->register->__invoke(request: $request, response: new Response());

        /** @Then the point should be registered successfully */
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
            ->willReturn($pointOfInterest);
    }
}
