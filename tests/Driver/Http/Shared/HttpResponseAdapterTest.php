<?php

namespace PointsOfInterest\Driver\Http\Shared;

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

    /**
     * @dataProvider providerForTestUnexpectedError
     */
    public function testUnexpectedError(int $code): void
    {
        /** @Dado que tenho uma query string válida */
        $query = sprintf('code=%s', $code);

        /** @E que eu faça uma solicitação usando essa query */
        $request = $this->request(query: $query);

        /** @Quando a operação for executada com essa solicitação */
        $response = $this->actionMock->__invoke(request: $request, response: new Response());

        /** @Então um erro com status 500 deve ser retornado */
        self::assertEquals(HttpCode::INTERNAL_SERVER_ERROR->value, $response->getStatusCode());
    }

    public function providerForTestUnexpectedError(): array
    {
        return [
            ['code' => -1],
            ['code' => 0],
            ['code' => 1054]
        ];
    }

    private function request(string $query = ''): SlimRequest
    {
        return RequestHttpMock::getRequest(path: '/pois', query: $query);
    }
}
