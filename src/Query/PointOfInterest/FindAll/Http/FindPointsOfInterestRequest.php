<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll\Http;

use PointsOfInterest\Query\PointOfInterest\FindAll\Proximity;
use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\HttpQuery\Cursor\Criteria;
use TinyBlocks\HttpQuery\Cursor\Keyset;
use TinyBlocks\HttpQuery\Operator;
use TinyBlocks\HttpQuery\Schema;
use TinyBlocks\HttpQuery\Sort;
use TinyBlocks\HttpQuery\ValueKind;

final readonly class FindPointsOfInterestRequest
{
    private function __construct(public Keyset $keyset, public ?Proximity $proximity, public array $comparisons)
    {
    }

    public static function from(ServerRequestInterface $request): FindPointsOfInterestRequest
    {
        $coordinate = [
            Operator::EQUAL,
            Operator::LESS_THAN_OR_EQUAL,
            Operator::GREATER_THAN_OR_EQUAL
        ];

        $schema = Schema::create()
            ->sortable(fields: ['id', 'name', 'created_at'])
            ->filterable(
                field: 'name',
                operators: [Operator::EQUAL, Operator::IN, Operator::STARTS_WITH],
                valueKind: ValueKind::STRING
            )
            ->filterable(field: 'x_coordinate', operators: $coordinate, valueKind: ValueKind::INTEGER)
            ->filterable(field: 'y_coordinate', operators: $coordinate, valueKind: ValueKind::INTEGER)
            ->defaultSort(sort: Sort::fromExpression(expression: '-created_at,-id'));

        $criteria = Criteria::fromQuery(schema: $schema, request: $request);

        return new FindPointsOfInterestRequest(
            keyset: $criteria->keyset(),
            proximity: Proximity::fromQueryParameters(parameters: (array)$request->getQueryParams()),
            comparisons: $criteria->comparisons()
        );
    }
}
