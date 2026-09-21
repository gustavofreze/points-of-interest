<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\Shared\Database\MySql;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PointsOfInterest\Driven\Shared\Database\DatabaseConstraint;
use PointsOfInterest\Driven\Shared\Database\DatabaseFailure;
use PointsOfInterest\Driven\Shared\Database\RelationalConnection;
use PointsOfInterest\Driven\Shared\Database\Result;
use PointsOfInterest\Driven\Shared\Database\Row;

final readonly class MySqlEngine implements RelationalConnection
{
    public function __construct(private Connection $connection)
    {
    }

    public function execute(string $sql, array $bindings = []): Result
    {
        try {
            return new Result(affectedRows: (int)$this->connection->executeStatement($sql, $bindings));
        } catch (UniqueConstraintViolationException $exception) {
            throw DatabaseFailure::dueTo(error: $exception, constraint: DatabaseConstraint::UNIQUE);
        } catch (DbalException $exception) {
            throw DatabaseFailure::from(error: $exception);
        }
    }

    public function fetchOne(string $sql, array $bindings = []): Row
    {
        try {
            $row = $this->connection->fetchAssociative($sql, $bindings);
        } catch (DbalException $exception) {
            throw DatabaseFailure::from(error: $exception);
        }

        return Row::from(values: $row === false ? null : $row);
    }

    public function fetchAll(string $sql, array $bindings = []): array
    {
        try {
            return $this->connection->fetchAllAssociative($sql, $bindings);
        } catch (DbalException $exception) {
            throw DatabaseFailure::from(error: $exception);
        }
    }

    public function inTransaction(Closure $useCase): mixed
    {
        try {
            return $this->connection->transactional(fn(): mixed => $useCase($this));
        } catch (DbalException $exception) {
            throw DatabaseFailure::from(error: $exception);
        }
    }
}
