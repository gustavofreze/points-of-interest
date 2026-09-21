<?php

declare(strict_types=1);

namespace Test\Integration\Application\Handlers;

use PHPUnit\Framework\Attributes\DataProvider;
use PointsOfInterest\Application\Commands\RegisterPointOfInterest;
use PointsOfInterest\Application\Domain\Models\Commons\Name;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\Coordinates;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterestId;
use PointsOfInterest\Application\Exceptions\PointOfInterestAlreadyExists;
use PointsOfInterest\Application\Ports\Inbound\PointOfInterestRegistration;
use Test\Integration\IntegrationTestCase;

final class PointOfInterestRegistrationHandlerTest extends IntegrationTestCase
{
    public static function pointProvider(): array
    {
        return [
            'At the origin'        => ['name' => 'Origem', 'xCoordinate' => 0, 'yCoordinate' => 0],
            'Inside the plane'     => ['name' => 'Lanchonete', 'xCoordinate' => 27, 'yCoordinate' => 12],
            'At the widest value'  => ['name' => 'Fim', 'xCoordinate' => 4294967295, 'yCoordinate' => 4294967295]
        ];
    }

    #[DataProvider('pointProvider')]
    public function testRecordsThePointAndItsFact(string $name, int $xCoordinate, int $yCoordinate): void
    {
        /** @Given a registration command */
        $id = PointOfInterestId::generate();
        $command = new RegisterPointOfInterest(
            id: $id,
            name: Name::from(value: $name),
            coordinates: Coordinates::from(xCoordinate: $xCoordinate, yCoordinate: $yCoordinate)
        );

        /** @When the command is handled */
        $this->get(PointOfInterestRegistration::class)->handle(command: $command);

        /** @Then the point of interest is recorded */
        self::assertSame(1, $this->fixtures()->pointOfInterestCountOf(name: $name));

        /** @And the fact is in the outbox with the coordinates it was registered at */
        $identifier = $id->identityValue();

        self::assertSame(
            ['PointOfInterestRegistered'],
            $this->fixtures()->outboxEventTypesOf(pointOfInterestId: $identifier)
        );

        $payload = $this->fixtures()->outboxPayloadOf(
            eventType: 'PointOfInterestRegistered',
            pointOfInterestId: $identifier
        );

        self::assertSame(['name', 'x_coordinate', 'y_coordinate'], array_keys($payload));
        self::assertSame($name, $payload['name']);
        self::assertSame($xCoordinate, $payload['x_coordinate']);
        self::assertSame($yCoordinate, $payload['y_coordinate']);
    }

    public function testRefusesAPointAlreadyRegisteredAtTheSameCoordinates(): void
    {
        /** @Given a point of interest already registered */
        $registration = $this->get(PointOfInterestRegistration::class);

        $registration->handle(command: new RegisterPointOfInterest(
            id: PointOfInterestId::generate(),
            name: Name::from(value: 'Pub'),
            coordinates: Coordinates::from(xCoordinate: 12, yCoordinate: 8)
        ));

        /** @Then the second registration is refused */
        $this->expectException(PointOfInterestAlreadyExists::class);

        /** @When the same name and coordinates are registered again */
        $registration->handle(command: new RegisterPointOfInterest(
            id: PointOfInterestId::generate(),
            name: Name::from(value: 'Pub'),
            coordinates: Coordinates::from(xCoordinate: 12, yCoordinate: 8)
        ));
    }

    public function testAcceptsTheSameNameAtDifferentCoordinates(): void
    {
        /** @Given a point of interest already registered */
        $registration = $this->get(PointOfInterestRegistration::class);

        $registration->handle(command: new RegisterPointOfInterest(
            id: PointOfInterestId::generate(),
            name: Name::from(value: 'Posto'),
            coordinates: Coordinates::from(xCoordinate: 31, yCoordinate: 18)
        ));

        /** @When the same name is registered elsewhere on the plane */
        $registration->handle(command: new RegisterPointOfInterest(
            id: PointOfInterestId::generate(),
            name: Name::from(value: 'Posto'),
            coordinates: Coordinates::from(xCoordinate: 12, yCoordinate: 8)
        ));

        /** @Then both branches are recorded */
        self::assertSame(2, $this->fixtures()->pointOfInterestCountOf(name: 'Posto'));
    }
}
