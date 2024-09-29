<?php

namespace PointsOfInterest\Driver\Http\Shared;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Mock\ActionMock;
use PointsOfInterest\Mock\RequestHttpMock;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response;
use TinyBlocks\Http\HttpCode;

final class HttpResponseAdapterTest extends TestCase
{
    private ActionMock $actionMock;

    protected function setUp(): void
    {
        $this->actionMock = new ActionMock();
    }

    #[DataProvider('providerForTestUnexpectedError')]
    public function testUnexpectedError(int $code): void
    {
        /** @Given that I have a valid query string */
        $query = sprintf('code=%s', $code);

        /** @And that I make a request using this query */
        $request = $this->request(query: $query);

        /** @When the operation is executed with this request */
        $response = $this->actionMock->__invoke(request: $request, response: new Response());

        /** @Then an error with status 500 should be returned */
        self::assertEquals(HttpCode::INTERNAL_SERVER_ERROR->value, $response->getStatusCode());
    }

    public static function providerForTestUnexpectedError(): iterable
    {
        yield 'zero code' => ['code' => 0];

        yield 'invalid code' => ['code' => 1054];

        yield 'negative code' => ['code' => -1];
    }

    private function request(string $query = ''): SlimRequest
    {
        return RequestHttpMock::getRequest(path: '/pois', query: $query);
    }
}
