<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\Shared\Database;

use Closure;

/**
 * Relational database connection for reads, writes, and transactional work.
 */
interface RelationalConnection
{
    /**
     * Issues the statement and returns the result with its affected-row count.
     *
     * @param array<string, scalar|null> $bindings The named values bound into the statement.
     * @throws DatabaseFailure When the statement fails at the database.
     */
    public function execute(string $sql, array $bindings = []): Result;

    /**
     * Returns the first row the query yields, empty when none matches.
     *
     * @param array<string, scalar|null> $bindings The named values bound into the query.
     * @throws DatabaseFailure When the query fails at the database.
     */
    public function fetchOne(string $sql, array $bindings = []): Row;

    /**
     * Returns every row the query yields, empty when none matches.
     *
     * @param array<string, scalar|null> $bindings The named values bound into the query.
     * @return array<int, array<string, mixed>> The rows the query yielded.
     * @throws DatabaseFailure When the query fails at the database.
     */
    public function fetchAll(string $sql, array $bindings = []): array;

    /**
     * Wraps the given work in a single transaction and returns its result. The work commits as one
     * unit, and any failure rolls the whole unit back.
     *
     * @template TReturn
     * @param Closure(RelationalConnection): TReturn $useCase The work to run inside the transaction.
     * @return TReturn The value the work produces.
     * @throws DatabaseFailure When the transaction fails at the database.
     */
    public function inTransaction(Closure $useCase): mixed;
}
