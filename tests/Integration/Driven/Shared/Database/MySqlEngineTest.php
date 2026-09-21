<?php

declare(strict_types=1);

namespace Test\Integration\Driven\Shared\Database;

use PointsOfInterest\Driven\Shared\Database\DatabaseConstraint;
use PointsOfInterest\Driven\Shared\Database\DatabaseFailure;
use PointsOfInterest\Driven\Shared\Database\RelationalConnection;
use Test\Integration\IntegrationTestCase;

final class MySqlEngineTest extends IntegrationTestCase
{
    private const string INSERT = '
        INSERT INTO points_of_interest (id, name, x_coordinate, y_coordinate)
        VALUES (UUID_TO_BIN(:id), :name, :xCoordinate, :yCoordinate)
    ';

    public function testReportsAUniqueConstraintViolation(): void
    {
        /** @Given a point of interest that is already registered */
        $connection = $this->get(RelationalConnection::class);

        $this->fixtures()->insertPointOfInterest(
            id: '0193b3a1-0000-7000-8000-00000000000a',
            name: 'Pub',
            createdAt: '2026-09-21 10:00:00.000000',
            xCoordinate: 12,
            yCoordinate: 8
        );

        /** @When the same name and coordinates are inserted again */
        try {
            $connection->execute(sql: MySqlEngineTest::INSERT, bindings: [
                'id'          => '0193b3a1-0000-7000-8000-00000000000b',
                'name'        => 'Pub',
                'xCoordinate' => 12,
                'yCoordinate' => 8
            ]);
            self::fail('The unique constraint on the point of interest was not enforced.');
        } catch (DatabaseFailure $failure) {
            /** @Then the failure names the constraint that was violated and keeps the driver cause */
            self::assertTrue($failure->hasViolated(constraint: DatabaseConstraint::UNIQUE));
            self::assertNotNull($failure->getPrevious());
            self::assertSame($failure->getPrevious()->getMessage(), $failure->getMessage());
        }
    }

    public function testReportsAFailureThatViolatedNoConstraint(): void
    {
        /** @Given a query against a table that does not exist */
        $connection = $this->get(RelationalConnection::class);

        /** @When it is issued */
        try {
            $connection->fetchAll(sql: 'SELECT * FROM table_that_does_not_exist');
            self::fail('The missing table did not fail.');
        } catch (DatabaseFailure $failure) {
            /** @Then the failure names no constraint */
            self::assertFalse($failure->hasViolated(constraint: DatabaseConstraint::UNIQUE));
        }
    }

    public function testWrapsAStatementFailureThatViolatedNoConstraint(): void
    {
        /** @Given a statement against a table that does not exist */
        $connection = $this->get(RelationalConnection::class);

        /** @When it is issued as a write */
        try {
            $connection->execute(sql: 'INSERT INTO table_that_does_not_exist (id) VALUES (1)');
            self::fail('The missing table did not fail.');
        } catch (DatabaseFailure $failure) {
            /** @Then it still arrives as a database failure, naming no constraint */
            self::assertFalse($failure->hasViolated(constraint: DatabaseConstraint::UNIQUE));
        }
    }

    public function testCountsTheRowsAStatementAffected(): void
    {
        /** @Given a recorded point of interest */
        $connection = $this->get(RelationalConnection::class);

        $this->fixtures()->insertPointOfInterest(
            id: '0193b3a1-0000-7000-8000-00000000000c',
            name: 'Joalheria',
            createdAt: '2026-09-21 10:00:00.000000',
            xCoordinate: 15,
            yCoordinate: 12
        );

        /** @When it is deleted */
        $result = $connection->execute(sql: 'DELETE FROM points_of_interest');

        /** @Then the affected row count is reported */
        self::assertSame(1, $result->affectedRows());
    }

    public function testAnswersAnEmptyRowWhenNothingMatches(): void
    {
        /** @Given no point of interest is recorded */
        $connection = $this->get(RelationalConnection::class);

        /** @When one is looked up */
        $row = $connection->fetchOne(sql: 'SELECT BIN_TO_UUID(id) AS id FROM points_of_interest LIMIT 1');

        /** @Then the row is empty and maps to nothing */
        self::assertNull($row->getOrNull());
        self::assertNull($row->map(transform: static fn(array $values): string => (string)$values['id'])->getOrNull());
    }

    public function testMapsTheRowItFound(): void
    {
        /** @Given a recorded point of interest */
        $connection = $this->get(RelationalConnection::class);

        $this->fixtures()->insertPointOfInterest(
            id: '0193b3a1-0000-7000-8000-00000000000d',
            name: 'Floricultura',
            createdAt: '2026-09-21 10:00:00.000000',
            xCoordinate: 19,
            yCoordinate: 21
        );

        /** @When it is looked up */
        $row = $connection->fetchOne(sql: 'SELECT BIN_TO_UUID(id) AS id FROM points_of_interest LIMIT 1');

        /** @Then the row maps to the value it carries */
        self::assertSame(
            '0193b3a1-0000-7000-8000-00000000000d',
            $row->map(transform: static fn(array $values): string => (string)$values['id'])->getOrNull()
        );
    }

    public function testRollsTheWholeUnitBackWhenTheWorkFails(): void
    {
        /** @Given work that records a point of interest and then fails */
        $connection = $this->get(RelationalConnection::class);

        /** @When it runs inside a transaction */
        try {
            $connection->inTransaction(useCase: function (RelationalConnection $transactional): void {
                $transactional->execute(sql: MySqlEngineTest::INSERT, bindings: [
                    'id'          => '0193b3a1-0000-7000-8000-00000000000e',
                    'name'        => 'Supermercado',
                    'xCoordinate' => 23,
                    'yCoordinate' => 6
                ]);
                $transactional->execute(sql: 'INSERT INTO table_that_does_not_exist (id) VALUES (1)');
            });
            self::fail('The failing unit of work did not fail.');
        } catch (DatabaseFailure) {
            /** @Then nothing the unit recorded survives */
            self::assertSame(0, $this->fixtures()->pointOfInterestCountOf(name: 'Supermercado'));
        }
    }
}
