<?php

declare(strict_types=1);

namespace Test\Unit\Driven\PointOfInterest\Repository;

use Closure;
use PointsOfInterest\Driven\Shared\Database\DatabaseFailure;
use PointsOfInterest\Driven\Shared\Database\RelationalConnection;
use PointsOfInterest\Driven\Shared\Database\Result;
use PointsOfInterest\Driven\Shared\Database\Row;

final class RelationalConnectionSpy implements RelationalConnection
{
    private const int NO_AFFECTED_ROWS = 0;

    private array $statements = [];

    public function __construct(private readonly ?DatabaseFailure $failureOnExecute = null)
    {
    }

    public function execute(string $sql, array $bindings = []): Result
    {
        $this->statements[] = $sql;

        if (!is_null($this->failureOnExecute)) {
            throw $this->failureOnExecute;
        }

        return new Result(affectedRows: self::NO_AFFECTED_ROWS);
    }

    public function fetchOne(string $sql, array $bindings = []): Row
    {
        $this->statements[] = $sql;

        return Row::from(values: null);
    }

    public function fetchAll(string $sql, array $bindings = []): array
    {
        $this->statements[] = $sql;

        return [];
    }

    public function inTransaction(Closure $useCase): mixed
    {
        return $useCase($this);
    }

    public function statementsIssued(): int
    {
        return count($this->statements);
    }
}
