<?php

declare(strict_types=1);

namespace Test\Unit\Application\Domain\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Application\Domain\Exceptions\CoordinateOutOfRange;
use PointsOfInterest\Application\Domain\Exceptions\EmptyName;
use PointsOfInterest\Application\Domain\Exceptions\NameTooLong;
use PointsOfInterest\Application\Domain\Models\Commons\Name;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\Coordinate;

final class BoundariesTest extends TestCase
{
    public static function acceptedCoordinateProvider(): array
    {
        return [
            'Origin'          => ['value' => 0],
            'Ordinary value'  => ['value' => 27],
            'Widest accepted' => ['value' => 4294967295]
        ];
    }

    public static function refusedCoordinateProvider(): array
    {
        return [
            'Just below zero'  => ['value' => -1],
            'Far below zero'   => ['value' => -4294967295],
            'Just above range' => ['value' => 4294967296]
        ];
    }

    public static function acceptedNameProvider(): array
    {
        return [
            'Shortest accepted' => ['value' => 'a'],
            'Ordinary name'     => ['value' => 'Lanchonete'],
            'Longest accepted'  => ['value' => 'aaaaaaaaaa']
        ];
    }

    #[DataProvider('acceptedCoordinateProvider')]
    public function testAcceptsACoordinateWithinRange(int $value): void
    {
        /** @Given a coordinate inside the stored range */
        /** @When the coordinate is built */
        $actual = Coordinate::from(value: $value);

        /** @Then it carries the value unchanged */
        self::assertSame($value, $actual->value);
    }

    #[DataProvider('refusedCoordinateProvider')]
    public function testRefusesACoordinateOutsideRange(int $value): void
    {
        /** @Given a coordinate outside the stored range */
        /** @Then the coordinate is refused */
        $this->expectException(CoordinateOutOfRange::class);
        $this->expectExceptionMessage(
            sprintf('Coordinate is out of range. Current <%d>, Minimum <0>, Maximum <4294967295>.', $value)
        );

        /** @When the coordinate is built */
        Coordinate::from(value: $value);
    }

    #[DataProvider('acceptedNameProvider')]
    public function testAcceptsANameWithinLength(string $value): void
    {
        /** @Given a name within the allowed length */
        /** @When the name is built */
        $actual = Name::from(value: $value);

        /** @Then it carries the value unchanged */
        self::assertSame($value, $actual->value);
    }

    public function testRefusesAnEmptyName(): void
    {
        /** @Given an empty name */
        /** @Then the name is refused */
        $this->expectException(EmptyName::class);
        $this->expectExceptionMessage('Name cannot be empty.');

        /** @When the name is built */
        Name::from(value: '');
    }

    public function testRefusesANameBeyondTheAllowedLength(): void
    {
        /** @Given a name one character past the allowed length */
        /** @Then the name is refused */
        $this->expectException(NameTooLong::class);
        $this->expectExceptionMessage('Name is too long. Current <256> characters, Maximum <255> characters.');

        /** @When the name is built */
        Name::from(value: str_repeat('a', 256));
    }

    public function testAcceptsANameAtTheAllowedLength(): void
    {
        /** @Given a name exactly at the allowed length */
        $value = str_repeat('a', 255);

        /** @When the name is built */
        $actual = Name::from(value: $value);

        /** @Then it carries the value unchanged */
        self::assertSame($value, $actual->value);
    }

    public function testMeasuresTheNameInCharactersAndNotInBytes(): void
    {
        /** @Given an accented name of 255 characters, which spans 510 bytes */
        $value = str_repeat('á', 255);

        /** @When the name is built */
        $actual = Name::from(value: $value);

        /** @Then it is accepted, because the limit counts characters */
        self::assertSame(255, mb_strlen($actual->value));
        self::assertSame(510, strlen($actual->value));
    }
}
