<?php

declare(strict_types=1);

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Exceptions\NameCannotBeEmpty;

final class NameTest extends TestCase
{
    public function testNameCannotBeEmpty(): void
    {
        /** @Given an empty string is provided for the name */
        $emptyName = '';

        /** @Then a NameCannotBeEmpty exception should be thrown */
        self::expectException(NameCannotBeEmpty::class);
        self::expectExceptionMessage('The name cannot be empty.');

        /** @When attempting to instantiate a Name with the empty string */
        new Name(value: $emptyName);
    }
}
