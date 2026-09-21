<?php

declare(strict_types=1);

namespace Test\Unit\Application\Domain\Models\PointOfInterest;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Application\Domain\Events\PointOfInterestRegistered;
use PointsOfInterest\Application\Domain\Models\Commons\Name;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\Coordinates;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterest;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterestId;

final class PointOfInterestTest extends TestCase
{
    public function testCarriesWhatItWasRegisteredWith(): void
    {
        /** @Given a name and the coordinates a receiver reported */
        $id = PointOfInterestId::generate();
        $name = Name::from(value: 'Pub');
        $coordinates = Coordinates::from(xCoordinate: 12, yCoordinate: 8);

        /** @When the point of interest is registered */
        $actual = PointOfInterest::register(id: $id, name: $name, coordinates: $coordinates);

        /** @Then it carries the identity, the name, and the coordinates */
        self::assertSame($id, $actual->id);
        self::assertSame('Pub', $actual->name->value);
        self::assertSame(12, $actual->coordinates->xCoordinate->value);
        self::assertSame(8, $actual->coordinates->yCoordinate->value);
    }

    public function testRecordsTheFactOfItsRegistration(): void
    {
        /** @Given a point of interest */
        $pointOfInterest = PointOfInterest::register(
            id: PointOfInterestId::generate(),
            name: Name::from(value: 'Joalheria'),
            coordinates: Coordinates::from(xCoordinate: 15, yCoordinate: 12)
        );

        /** @When the recorded facts are read */
        $events = $pointOfInterest->peekEvents();

        /** @Then the registration was recorded with what the point carries */
        self::assertSame(1, $events->count());
        self::assertSame('PointOfInterestRegistered', $events->first()->eventType->value);

        $event = $events->first()->event;

        self::assertInstanceOf(PointOfInterestRegistered::class, $event);
        self::assertSame('Joalheria', $event->name->value);
        self::assertSame(15, $event->coordinates->xCoordinate->value);
        self::assertSame(12, $event->coordinates->yCoordinate->value);
    }

    public function testForgetsTheFactsOnceTheyArePulled(): void
    {
        /** @Given a point of interest that recorded its registration */
        $pointOfInterest = PointOfInterest::register(
            id: PointOfInterestId::generate(),
            name: Name::from(value: 'Posto'),
            coordinates: Coordinates::from(xCoordinate: 31, yCoordinate: 18)
        );

        /** @When the facts are pulled */
        $pulled = $pointOfInterest->pullEvents();

        /** @Then they are handed over once and no longer held */
        self::assertSame(1, $pulled->count());
        self::assertSame(0, $pointOfInterest->peekEvents()->count());
    }
}
