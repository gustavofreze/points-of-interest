<?php

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Exceptions\NameCannotBeEmpty;

final class NameTest extends TestCase
{
    public function testNameCannotBeEmpty(): void
    {
        $this->expectErrorMessage('The name cannot be empty.');
        $this->expectException(NameCannotBeEmpty::class);

        new Name(value: '');
    }
}
