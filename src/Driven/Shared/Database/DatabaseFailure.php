<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\Shared\Database;

use RuntimeException;
use Throwable;

final class DatabaseFailure extends RuntimeException
{
    private function __construct(Throwable $error, private readonly ?DatabaseConstraint $constraint)
    {
        parent::__construct(message: $error->getMessage(), previous: $error);
    }

    public static function from(Throwable $error): DatabaseFailure
    {
        return new DatabaseFailure(error: $error, constraint: null);
    }

    public static function dueTo(Throwable $error, DatabaseConstraint $constraint): DatabaseFailure
    {
        return new DatabaseFailure(error: $error, constraint: $constraint);
    }

    public function hasViolated(DatabaseConstraint $constraint): bool
    {
        return $this->constraint === $constraint;
    }
}
