<?php

namespace PointsOfInterest\Domain\Models;

use PHPUnit\Framework\TestCase;

final class PointsOfInterestTest extends TestCase
{
    /**
     * @dataProvider providerForTestByProximity
     */
    public function testByProximity(array $points, array $expected): void
    {
        /** @Dado que tenho um ponto de referência indicado pelo receptor GPS */
        $referencePoint = ReferencePoint::from(xCoordinate: 20, yCoordinate: 10);

        /** @E que tenho uma distância máxima de 10 metros */
        $maximumDistance = new Distance(value: 10);

        /** @E uma lista de pontos de interesse */
        $pointsOfInterest = new PointsOfInterest(values: $points);

        /** @Quando o método byProximity for executado */
        $actual = $pointsOfInterest->byProximity(referencePoint: $referencePoint, maximumDistance: $maximumDistance);

        /** @Então devem ser retornados 04 pontos de interesse */
        self::assertCount(4, $actual->values);

        /** @E os pontos retornados devem ser exatamente os da lista de pontos esperados */
        self::assertEquals($expected, $actual->values);
    }

    /**
     * @dataProvider providerForTestByExactProximity
     */
    public function testByExactProximity(array $points, array $expected): void
    {
        /** @Dado que tenho um ponto de referência indicado pelo receptor GPS */
        $referencePoint = ReferencePoint::from(xCoordinate: 0, yCoordinate: 9999);

        /** @E que tenho uma distância máxima de 0 metros */
        $maximumDistance = new Distance(value: 0);

        /** @E uma lista de pontos de interesse */
        $pointsOfInterest = new PointsOfInterest(values: $points);

        /** @Quando o método byProximity for executado */
        $actual = $pointsOfInterest->byProximity(referencePoint: $referencePoint, maximumDistance: $maximumDistance);

        /** @Então deve ser retornado um único ponto de interesse */
        self::assertCount(1, $actual->values);

        /** @E o ponto retornado deve ser exatamente o da lista de pontos esperados */
        self::assertEquals($expected, $actual->values);
    }

    public function providerForTestByProximity(): array
    {
        $expected = [
            PointOfInterest::from(name: 'Pub', xCoordinate: 12, yCoordinate: 8),
            PointOfInterest::from(name: 'Joalheria', xCoordinate: 15, yCoordinate: 12),
            PointOfInterest::from(name: 'Lanchonete', xCoordinate: 27, yCoordinate: 12),
            PointOfInterest::from(name: 'Supermercado', xCoordinate: 23, yCoordinate: 6)
        ];

        return [
            [
                'points'   => array_merge(
                    [
                        PointOfInterest::from(name: 'Posto', xCoordinate: 31, yCoordinate: 18),
                        PointOfInterest::from(name: 'Churrascaria', xCoordinate: 28, yCoordinate: 2),
                        PointOfInterest::from(name: 'Floricultura', xCoordinate: 19, yCoordinate: 21)
                    ],
                    $expected
                ),
                'expected' => $expected
            ]
        ];
    }

    public function providerForTestByExactProximity(): array
    {
        $expected = [
            PointOfInterest::from(name: 'Academia', xCoordinate: 0, yCoordinate: 9999)
        ];

        return [
            [
                'points'   => array_merge(
                    [
                        PointOfInterest::from(name: 'Posto', xCoordinate: 31, yCoordinate: 18),
                        PointOfInterest::from(name: 'Joalheria', xCoordinate: 15, yCoordinate: 12),
                        PointOfInterest::from(name: 'Lanchonete', xCoordinate: 27, yCoordinate: 12),
                        PointOfInterest::from(name: 'Churrascaria', xCoordinate: 28, yCoordinate: 2)
                    ],
                    $expected
                ),
                'expected' => $expected
            ]
        ];
    }
}
