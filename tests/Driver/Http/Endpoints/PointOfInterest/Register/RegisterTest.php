<?php


declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks\PointsMock;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Mocks\RequestHttpMock;
use TinyBlocks\Http\Code;

final class RegisterTest extends TestCase
{
    private Register $register;

    protected function setUp(): void
    {
        $this->register = new Register(points: new PointsMock());
    }

    public function testRegisterPointOfInterest(): void
    {
        /** @Given I have a valid point of interest with coordinates */
        $payload = ['name' => 'xpto', 'point' => ['x_coordinate' => rand(1, 10000), 'y_coordinate' => rand(1, 10000)]];

        /** @And I prepare a registration request with the point of interest data */
        $request = RequestHttpMock::postFrom(path: '/pois', payload: json_encode($payload));

        /** @When the register endpoint processes the request */
        $response = $this->register->handle(request: $request);

        /** @Then the point should be registered successfully with a 201 Created response */
        self::assertSame(Code::CREATED->value, $response->getStatusCode());
        self::assertSame($payload, json_decode($response->getBody()->__toString(), true));
    }
}
