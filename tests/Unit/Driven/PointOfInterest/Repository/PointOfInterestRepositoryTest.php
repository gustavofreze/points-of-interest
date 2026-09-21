<?php

declare(strict_types=1);

namespace Test\Unit\Driven\PointOfInterest\Repository;

use PHPUnit\Framework\TestCase;
use PointsOfInterest\Application\Exceptions\PointOfInterestAlreadyExists;
use PointsOfInterest\Driven\PointOfInterest\Repository\PointOfInterestRepository;
use PointsOfInterest\Driven\PointOfInterest\Repository\Records\PointOfInterestRecordWriter;
use PointsOfInterest\Driven\Shared\Database\DatabaseConstraint;
use PointsOfInterest\Driven\Shared\Database\DatabaseFailure;
use RuntimeException;
use Test\Unit\Driven\PointOfInterest\Outbox\OutboxRepositorySpy;
use Test\Unit\PointOfInterestFactory;

/**
 * Justification for a unit test on a repository (php-testing-unit § Default stance). The constraint-translation
 * branches the writer decides are chosen by which DatabaseFailure the connection raises, and the points of interest
 * table carries a single constraint, so the non-unique branch is unreachable against the real schema that the
 * integration suite exercises. The connection and the outbox are system boundaries, where php-testing § Doubles
 * admits a Spy, and the writer is a real object built over that same spy.
 */
final class PointOfInterestRepositoryTest extends TestCase
{
    public function testSaveIssuesTheInsertAndPublishesTheRecordedFact(): void
    {
        /** @Given a connection that accepts every statement and an outbox that accepts every record */
        $outbox = new OutboxRepositorySpy();
        $connection = new RelationalConnectionSpy();

        /** @And a registered point of interest */
        $pointOfInterest = PointOfInterestFactory::registered();

        /** @When the point of interest is saved */
        new PointOfInterestRepository(
            outbox: $outbox,
            writer: new PointOfInterestRecordWriter(connection: $connection),
            connection: $connection
        )->save(pointOfInterest: $pointOfInterest);

        /** @Then the insert reached the database and the registration reached the outbox */
        self::assertSame(1, $connection->statementsIssued());
        self::assertSame(1, $outbox->recordsPushed());
        self::assertSame(0, $pointOfInterest->peekEvents()->count());
    }

    public function testUniqueViolationBecomesPointOfInterestAlreadyExists(): void
    {
        /** @Given a connection that rejects the insert with a unique constraint violation */
        $failure = DatabaseFailure::dueTo(
            error: new RuntimeException('Duplicate entry'),
            constraint: DatabaseConstraint::UNIQUE
        );
        $connection = new RelationalConnectionSpy(failureOnExecute: $failure);

        /** @Then a PointOfInterestAlreadyExists is expected */
        $this->expectException(PointOfInterestAlreadyExists::class);

        /** @When the point of interest is saved */
        new PointOfInterestRepository(
            outbox: new OutboxRepositorySpy(),
            writer: new PointOfInterestRecordWriter(connection: $connection),
            connection: $connection
        )->save(pointOfInterest: PointOfInterestFactory::registered());
    }

    public function testDatabaseFailureWithoutConstraintIsRethrown(): void
    {
        /** @Given a connection that rejects the insert with a failure carrying no constraint */
        $failure = DatabaseFailure::from(error: new RuntimeException('Connection lost'));
        $connection = new RelationalConnectionSpy(failureOnExecute: $failure);

        /** @Then the original DatabaseFailure is expected */
        $this->expectException(DatabaseFailure::class);
        $this->expectExceptionMessage('Connection lost');

        /** @When the point of interest is saved */
        new PointOfInterestRepository(
            outbox: new OutboxRepositorySpy(),
            writer: new PointOfInterestRecordWriter(connection: $connection),
            connection: $connection
        )->save(pointOfInterest: PointOfInterestFactory::registered());
    }
}
