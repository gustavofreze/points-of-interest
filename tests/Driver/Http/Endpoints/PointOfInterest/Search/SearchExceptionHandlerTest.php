<?php


declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search;

use Exception;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks\PointsMock;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks\RequestHttpMock;
use PointsOfInterest\Driver\Http\Middlewares\ErrorHandling;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TinyBlocks\Http\Code;

final class SearchExceptionHandlerTest extends TestCase
{
    private Search $endpoint;
    private ErrorHandling $middleware;

    protected function setUp(): void
    {
        $this->endpoint = new Search(points: new PointsMock());

        $exceptionHandler = new SearchExceptionHandler();
        $this->middleware = new ErrorHandling(exceptionHandler: $exceptionHandler);
    }

    public function testExceptionWhenInvalidRequest(): void
    {
        /** @Given I have an invalid query string missing the required 'distance' parameter */
        $query = 'x_coordinate=20&y_coordinate=10';

        /** @And I prepare a search request with this invalid query */
        $request = RequestHttpMock::getFrom(path: '/pois', query: $query);

        /** @When I process the request through the middleware and endpoint */
        $actual = $this->middleware->process(request: $request, handler: $this->endpoint);

        /** @Then an error response indicating the missing 'distance' parameter should be returned */
        self::assertSame(Code::UNPROCESSABLE_ENTITY->value, $actual->getStatusCode());
        self::assertSame('{"error":{"distance":"distance must be present"}}', $actual->getBody()->__toString());
    }

    public function testExceptionWhenUnknownError(): void
    {
        /** @Given I have a valid query string with x_coordinate, y_coordinate, and distance */
        $query = 'x_coordinate=20&y_coordinate=10&distance=10';

        /** @And I prepare a search request with this valid query */
        $request = RequestHttpMock::getFrom(path: '/pois', query: $query);

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
