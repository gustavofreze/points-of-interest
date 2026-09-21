<?php

declare(strict_types=1);

namespace Test\Unit\Driver\Http\Endpoints\PointOfInterest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Application\Domain\Exceptions\CoordinateOutOfRange;
use PointsOfInterest\Application\Domain\Exceptions\EmptyName;
use PointsOfInterest\Application\Domain\Exceptions\NameTooLong;
use PointsOfInterest\Application\Exceptions\PointOfInterestAlreadyExists;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\RegisterPointOfInterest;
use PointsOfInterest\Driver\Http\InvalidRequest;
use Test\Unit\RequestFactory;
use TinyBlocks\Http\Code;

final class RegisterPointOfInterestTest extends TestCase
{
    public static function invalidPayloadProvider(): array
    {
        return [
            'Missing name'            => [
                'payload' => ['point' => ['x_coordinate' => 12, 'y_coordinate' => 8]]
            ],
            'Missing point'           => [
                'payload' => ['name' => 'Pub']
            ],
            'Missing x coordinate'    => [
                'payload' => ['name' => 'Pub', 'point' => ['y_coordinate' => 8]]
            ],
            'Missing y coordinate'    => [
                'payload' => ['name' => 'Pub', 'point' => ['x_coordinate' => 12]]
            ],
            'Name as a number'        => [
                'payload' => ['name' => 42, 'point' => ['x_coordinate' => 12, 'y_coordinate' => 8]]
            ],
            'Fractional coordinate'   => [
                'payload' => ['name' => 'Pub', 'point' => ['x_coordinate' => 12.5, 'y_coordinate' => 8]]
            ],
            'Coordinate as a string'  => [
                'payload' => ['name' => 'Pub', 'point' => ['x_coordinate' => '12', 'y_coordinate' => 8]]
            ]
        ];
    }

    public static function boundaryNameProvider(): array
    {
        return [
            'Shortest accepted name' => ['name' => 'a'],
            'Longest accepted name'  => ['name' => str_repeat('a', 255)]
        ];
    }

    #[DataProvider('boundaryNameProvider')]
    public function testAcceptsANameAtTheBoundary(string $name): void
    {
        /** @Given a registration whose name sits at a length boundary */
        $spy = new PointOfInterestRegistrationSpy();
        $endpoint = new RegisterPointOfInterest(registration: $spy);

        $request = RequestFactory::postFrom(payload: [
            'name'  => $name,
            'point' => ['x_coordinate' => 12, 'y_coordinate' => 8]
        ]);

        /** @When the request is handled */
        $actual = $endpoint->handle($request);

        /** @Then the request is accepted */
        self::assertSame(Code::CREATED->value, $actual->getStatusCode());
        self::assertSame($name, $spy->received->name->value);
    }

    public function testAnswersWithThePointOfInterestIdentifier(): void
    {
        /** @Given a valid registration */
        $spy = new PointOfInterestRegistrationSpy();
        $endpoint = new RegisterPointOfInterest(registration: $spy);

        $request = RequestFactory::postFrom(payload: [
            'name'  => 'Pub',
            'point' => ['x_coordinate' => 12, 'y_coordinate' => 8]
        ]);

        /** @When the request is handled */
        $actual = $endpoint->handle($request);

        /** @Then the point of interest is registered and its identifier answered */
        self::assertSame(Code::CREATED->value, $actual->getStatusCode());
        self::assertSame(
            ['id' => $spy->received->id->identityValue()],
            (array)json_decode($actual->getBody()->__toString(), true)
        );
    }

    public function testHandsTheCommandToTheApplication(): void
    {
        /** @Given a valid registration */
        $spy = new PointOfInterestRegistrationSpy();
        $endpoint = new RegisterPointOfInterest(registration: $spy);

        $request = RequestFactory::postFrom(payload: [
            'name'  => 'Lanchonete',
            'point' => ['x_coordinate' => 27, 'y_coordinate' => 12]
        ]);

        /** @When the request is handled */
        $endpoint->handle($request);

        /** @Then the command carries what the payload declared */
        self::assertSame('Lanchonete', $spy->received->name->value);
        self::assertSame(27, $spy->received->coordinates->xCoordinate->value);
        self::assertSame(12, $spy->received->coordinates->yCoordinate->value);
    }

    #[DataProvider('invalidPayloadProvider')]
    public function testRefusesAnInvalidPayload(array $payload): void
    {
        /** @Given an invalid registration */
        $endpoint = new RegisterPointOfInterest(registration: new PointOfInterestRegistrationSpy());

        /** @Then the request is refused */
        $this->expectException(InvalidRequest::class);

        /** @When the request is handled */
        $endpoint->handle(RequestFactory::postFrom(payload: $payload));
    }

    public function testRefusesAnEmptyName(): void
    {
        /** @Given a registration carrying an empty name */
        $endpoint = new RegisterPointOfInterest(registration: new PointOfInterestRegistrationSpy());

        $request = RequestFactory::postFrom(payload: [
            'name'  => '',
            'point' => ['x_coordinate' => 12, 'y_coordinate' => 8]
        ]);

        /** @Then the name is refused by the domain */
        $this->expectException(EmptyName::class);

        /** @When the request is handled */
        $endpoint->handle($request);
    }

    public function testRefusesANameBeyondTheAllowedLength(): void
    {
        /** @Given a registration carrying a name one character past the allowed length */
        $endpoint = new RegisterPointOfInterest(registration: new PointOfInterestRegistrationSpy());

        $request = RequestFactory::postFrom(payload: [
            'name'  => str_repeat('a', 256),
            'point' => ['x_coordinate' => 12, 'y_coordinate' => 8]
        ]);

        /** @Then the name is refused by the domain */
        $this->expectException(NameTooLong::class);

        /** @When the request is handled */
        $endpoint->handle($request);
    }

    public function testRefusesANegativeCoordinate(): void
    {
        /** @Given a registration carrying a negative coordinate */
        $endpoint = new RegisterPointOfInterest(registration: new PointOfInterestRegistrationSpy());

        $request = RequestFactory::postFrom(payload: [
            'name'  => 'Pub',
            'point' => ['x_coordinate' => -1, 'y_coordinate' => 8]
        ]);

        /** @Then the coordinate is refused by the domain */
        $this->expectException(CoordinateOutOfRange::class);

        /** @When the request is handled */
        $endpoint->handle($request);
    }

    public function testPropagatesTheApplicationFailure(): void
    {
        /** @Given an application that refuses the registration */
        $spy = new PointOfInterestRegistrationSpy(failure: new PointOfInterestAlreadyExists());
        $endpoint = new RegisterPointOfInterest(registration: $spy);

        $request = RequestFactory::postFrom(payload: [
            'name'  => 'Pub',
            'point' => ['x_coordinate' => 12, 'y_coordinate' => 8]
        ]);

        /** @Then the failure reaches the error boundary */
        $this->expectException(PointOfInterestAlreadyExists::class);

        /** @When the request is handled */
        $endpoint->handle($request);
    }
}
