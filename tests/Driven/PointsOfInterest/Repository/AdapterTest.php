<?php

namespace PointsOfInterest\Driven\PointsOfInterest\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Domain\Ports\Outbound\Points;

final class AdapterTest extends TestCase
{
    private Points $points;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->points = new Adapter(connection: $this->connection);
    }

    public function testSave(): void
    {
        /** @Dado que tenho um ponto de interesse válido */
        $pointOfInterest = $this->toAddPointOfInterest();

        /** @Quando for solicitado a persistência desse ponto de interesse */
        $this->points->save(pointOfInterest: $pointOfInterest);

        /** @Então não deve ocorrer nenhum erro */
        $this->addToAssertionCount(1);
    }

    public function testFindReturningPointOfInterest(): void
    {
        /** @Dado que tenho um ponto de interesse qualquer persistidos no banco de dados */
        $pointOfInterest = $this->addedPointOfInterest();

        /** @Quando esse ponto for solicitado */
        $actual = $this->points->find(pointOfInterest: $pointOfInterest);

        /** @Então os dados retornados devem ser iguais aos dados persistidos */
        self::assertEquals($pointOfInterest->values(), $actual->values());
    }

    public function testFindReturningNull(): void
    {
        /** @Dado um ponto de interesse de qualquer */
        $pointOfInterest = PointOfInterest::from(name: 'Pub', xCoordinate: rand(1, 1000), yCoordinate: rand(1, 1000));

        /** @E que esse ponto não esteja persistido no banco de dados */
        $this->removePointOfInterest(pointOfInterest: $pointOfInterest);

        /** @Quando esse ponto for solicitado */
        $actual = $this->points->find(pointOfInterest: $pointOfInterest);

        /** @Então não deve ser retornado nenhum ponto de interesse */
        self::assertNull($actual);
    }

    public function testFindAllReturningPointsOfInterest(): void
    {
        /** @Dado que tenho pontos de interesses persistidos no banco de dados */
        $pointsOfInterest = $this->addedPointsOfInterest(points: [
            PointOfInterest::from(name: 'Pub', xCoordinate: rand(1, 1000), yCoordinate: rand(1, 1000)),
            PointOfInterest::from(name: 'Churrascaria', xCoordinate: rand(1, 1000), yCoordinate: rand(1, 1000))
        ]);

        /** @Quando esses pontos são solicitados */
        $actual = $this->points->findAll();

        /** @Então eles devem ser retornados */
        self::assertCount(2, $actual->values);

        /** @E os dados retornados devem ser iguais aos pontos persistidos */
        self::assertEquals($pointsOfInterest, $actual->values);
    }

    public function testFindAllReturningEmpty(): void
    {
        /** @Dado que não tenho pontos de interesses persistidos no banco de dados */
        $this->removePointsOfInterest();

        /** @Quando esses pontos são solicitados */
        $actual = $this->points->findAll();

        /** @Então não deve ser retornado nenhum ponto de interesse */
        self::assertEmpty($actual->values);
        self::assertCount(0, $actual->values);
    }

    private function addedPointOfInterest(): PointOfInterest
    {
        $pointOfInterest = PointOfInterest::from(name: 'Pub', xCoordinate: rand(1, 1000), yCoordinate: rand(1, 1000));

        $point = [
            'name'        => $pointOfInterest->name->value,
            'xCoordinate' => $pointOfInterest->xCoordinate->value,
            'yCoordinate' => $pointOfInterest->yCoordinate->value
        ];

        $result = $this->createMock(Result::class);
        $result->expects(self::once())
            ->method('fetchAssociative')
            ->will(self::returnValue($point));

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with(Queries::FIND, [
                $pointOfInterest->name->value,
                $pointOfInterest->xCoordinate->value,
                $pointOfInterest->yCoordinate->value
            ])
            ->will(self::returnValue($result));

        return $pointOfInterest;
    }

    private function toAddPointOfInterest(): PointOfInterest
    {
        $pointOfInterest = PointOfInterest::from(name: 'Pub', xCoordinate: rand(1, 1000), yCoordinate: rand(1, 1000));

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with(Queries::INSERT, [
                $pointOfInterest->name->value,
                $pointOfInterest->xCoordinate->value,
                $pointOfInterest->yCoordinate->value
            ]);

        return $pointOfInterest;
    }

    private function addedPointsOfInterest(array $points): array
    {
        $mapper = fn(PointOfInterest $pointOfInterest) => ([
            'name'        => $pointOfInterest->name->value,
            'xCoordinate' => $pointOfInterest->xCoordinate->value,
            'yCoordinate' => $pointOfInterest->yCoordinate->value
        ]);

        $result = $this->createMock(Result::class);
        $result->expects(self::once())
            ->method('fetchAllAssociative')
            ->will(self::returnValue(array_map($mapper, $points)));

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with(Queries::FIND_ALL, [])
            ->will(self::returnValue($result));

        return $points;
    }

    private function removePointOfInterest(PointOfInterest $pointOfInterest): void
    {
        $result = $this->createMock(Result::class);

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with(Queries::FIND, [
                $pointOfInterest->name->value,
                $pointOfInterest->xCoordinate->value,
                $pointOfInterest->yCoordinate->value
            ])
            ->will(self::returnValue($result));
    }

    private function removePointsOfInterest(): void
    {
        $result = $this->createMock(Result::class);

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with(Queries::FIND_ALL, [])
            ->will(self::returnValue($result));
    }
}
