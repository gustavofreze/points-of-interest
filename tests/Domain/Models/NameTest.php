<?php

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Exceptions\NameCannotBeEmpty;

final class NameTest extends TestCase
{
    public function testNameCannotBeEmpty(): void
    {
        self::expectException(NameCannotBeEmpty::class);
        self::expectExceptionMessage('The name cannot be empty.');

        new Name(value: '');
    }
}
