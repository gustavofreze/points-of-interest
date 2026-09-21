<?php

declare(strict_types=1);

namespace Test\Unit\Query\PointOfInterest\FindAll\ReadModel;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Query\PointOfInterest\FindAll\ReadModel\PointOfInterest;

final class PointOfInterestTest extends TestCase
{
    public function testRendersEveryFieldTheContractPublishes(): void
    {
        /** @Given a point of interest read model */
        $pointOfInterest = PointOfInterest::from(
            id: '0193b3a1-0000-7000-8000-000000000001',
            name: 'Lanchonete',
            createdAt: '2026-09-21T10:00:00.000000+00:00',
            xCoordinate: 27,
            yCoordinate: 12
        );

        /** @When it is rendered */
        $actual = $pointOfInterest->toArray();

        /** @Then every field is published in snake case */
        self::assertSame(
            [
                'id'         => '0193b3a1-0000-7000-8000-000000000001',
                'name'       => 'Lanchonete',
                'point'      => [
                    'x_coordinate' => 27,
                    'y_coordinate' => 12
                ],
                'created_at' => '2026-09-21T10:00:00.000000+00:00'
            ],
            $actual
        );
    }
}
