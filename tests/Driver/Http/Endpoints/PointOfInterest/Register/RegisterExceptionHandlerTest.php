<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register;

use Exception;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Ports\Outbound\Points;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks\PointsMock;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks\RequestHttpMock;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\Dtos\Request;
use PointsOfInterest\Driver\Http\Middlewares\ErrorHandling;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TinyBlocks\Http\Code;

final class RegisterExceptionHandlerTest extends TestCase
{
    private Points $points;
    private Register $endpoint;
    private ErrorHandling $middleware;

    protected function setUp(): void
    {
        $this->points = new PointsMock();
        $this->endpoint = new Register(points: $this->points);

        $exceptionHandler = new RegisterExceptionHandler();
        $this->middleware = new ErrorHandling(exceptionHandler: $exceptionHandler);
    }

    public function testExceptionWhenInvalidRequest(): void
    {
        /** @Given I have an invalid point of interest payload without the required 'name' field */
        $payload = ['point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @And I send a registration request with this invalid payload */
        $request = RequestHttpMock::postFrom(path: '/pois', payload: json_encode($payload));

        /** @When I process the request through the middleware and endpoint */
        $actual = $this->middleware->process(request: $request, handler: $this->endpoint);

        /** @Then I should receive an error response indicating the 'name' field is missing */
        self::assertSame(Code::UNPROCESSABLE_ENTITY->value, $actual->getStatusCode());
        self::assertSame('{"error":{"name":"name must be present"}}', $actual->getBody()->__toString());
    }

    public function testExceptionWhenNegativeCoordinate(): void
    {
        /** @Given I have an invalid point with a negative x-coordinate */
        $payload = ['name' => 'xpto', 'point' => ['x_coordinate' => -1000, 'y_coordinate' => rand(1, 10000)]];

        /** @And I send a registration request with this invalid point data */
        $request = RequestHttpMock::postFrom(path: '/pois', payload: json_encode($payload));

        /** @When I process the request through the middleware and endpoint */
        $actual = $this->middleware->process(request: $request, handler: $this->endpoint);

        /** @Then I should receive an error response indicating that the x-coordinate cannot be negative */
        self::assertSame(Code::UNPROCESSABLE_ENTITY->value, $actual->getStatusCode());
        self::assertSame('{"error":"Coordinate value <-1000> cannot be negative."}', $actual->getBody()->__toString());
    }

    public function testExceptionWhenPointOfInterestAlreadyExists(): void
    {
        /** @Given I have a valid point of interest with a unique name */
        $payload = ['name' => 'xpto', 'point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @And this point of interest has already been registered previously */
        $this->points->save(pointOfInterest: (new Request(payload: $payload))->toPointOfInterest());

        /** @And I send a registration request with the same point of interest details */
        $request = RequestHttpMock::postFrom(path: '/pois', payload: json_encode($payload));

        /** @When I process the request through the middleware and endpoint */
        $actual = $this->middleware->process(request: $request, handler: $this->endpoint);

        /** @Then I should receive an error response indicating that the point already exists */
        $template = 'A point of interest with name <%s>, x coordinate <%s> and y coordinate <%s> already exists.';
        $expected = json_encode([
            'error' => sprintf(
                $template,
                $payload['name'],
                $payload['point']['x_coordinate'],
                $payload['point']['y_coordinate']
            )
        ]);
        self::assertSame(Code::CONFLICT->value, $actual->getStatusCode());
        self::assertSame($expected, $actual->getBody()->__toString());
    }

    public function testExceptionWhenUnknownError(): void
    {
        /** @Given I have a valid point of interest with coordinates */
        $payload = ['name' => 'xpto', 'point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @And I prepare a registration request with the point of interest data */
        $request = RequestHttpMock::postFrom(path: '/pois', payload: json_encode($payload));

        /** @And I create an endpoint that throws an unknown error when handling the request */
        $endpoint = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new Exception('Unknown error occurred.');
            }
        };

        /** @When I process the request through the middleware and endpoint */
        $actual = $this->middleware->process(request: $request, handler: $endpoint);

        /** @Then I should receive an error response indicating that an unknown error occurred */
        self::assertSame(Code::INTERNAL_SERVER_ERROR->value, $actual->getStatusCode());
        self::assertSame('{"error":"Unknown error occurred."}', $actual->getBody()->__toString());
    }
}
