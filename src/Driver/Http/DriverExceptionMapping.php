<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http;

use PointsOfInterest\Application\Domain\Exceptions\CoordinateOutOfRange;
use PointsOfInterest\Application\Domain\Exceptions\EmptyName;
use PointsOfInterest\Application\Domain\Exceptions\NameTooLong;
use PointsOfInterest\Application\Exceptions\PointOfInterestAlreadyExists;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\ErrorHandler\ExceptionMapping;
use TinyBlocks\Http\ErrorHandler\ExceptionMappingTable;
use TinyBlocks\Http\ErrorHandler\MappedError;

final readonly class DriverExceptionMapping implements ExceptionMapping
{
    public function mappings(): ExceptionMappingTable
    {
        return ExceptionMappingTable::create()
            ->when(exceptionClass: PointOfInterestAlreadyExists::class)
            ->mapsTo(
                code: 'POINT_OF_INTEREST_ALREADY_EXISTS',
                status: Code::CONFLICT->value,
                message: 'A point of interest with this name already exists at these coordinates.'
            )
            ->when(exceptionClass: CoordinateOutOfRange::class)
            ->resolvesWith(resolver: static fn(CoordinateOutOfRange $error): MappedError => new MappedError(
                code: 'COORDINATE_OUT_OF_RANGE',
                status: Code::UNPROCESSABLE_ENTITY->value,
                message: $error->getMessage()
            ))
            ->whenAny(exceptionClasses: [EmptyName::class, NameTooLong::class])
            ->resolvesWith(resolver: static fn(EmptyName|NameTooLong $error): MappedError => new MappedError(
                code: 'INVALID_NAME',
                status: Code::UNPROCESSABLE_ENTITY->value,
                message: $error->getMessage()
            ))
            ->when(exceptionClass: InvalidRequest::class)
            ->resolvesWith(resolver: static fn(InvalidRequest $error): MappedError => new MappedError(
                code: 'INVALID_REQUEST',
                status: Code::UNPROCESSABLE_ENTITY->value,
                message: implode(', ', $error->messages)
            ));
    }
}
