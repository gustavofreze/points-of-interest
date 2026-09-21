<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\Shared\Http;

use TinyBlocks\Http\Code;
use TinyBlocks\Http\ErrorHandler\ExceptionMapping;
use TinyBlocks\Http\ErrorHandler\ExceptionMappingTable;
use TinyBlocks\Http\ErrorHandler\MappedError;
use TinyBlocks\HttpQuery\Exceptions\HttpQueryException;

final readonly class QueryExceptionMapping implements ExceptionMapping
{
    public function mappings(): ExceptionMappingTable
    {
        return ExceptionMappingTable::create()
            ->whenSubclassOf(baseException: HttpQueryException::class)
            ->resolvesWith(resolver: static fn(HttpQueryException $error): MappedError => new MappedError(
                code: 'INVALID_REQUEST',
                status: Code::UNPROCESSABLE_ENTITY->value,
                message: $error->getMessage()
            ))
            ->when(exceptionClass: InvalidRequest::class)
            ->resolvesWith(resolver: static fn(InvalidRequest $error): MappedError => new MappedError(
                code: 'INVALID_REQUEST',
                status: Code::UNPROCESSABLE_ENTITY->value,
                message: $error->reason
            ));
    }
}
