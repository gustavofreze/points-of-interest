<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointsOfInterest\Repository\Mocks;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PointsOfInterest\Driven\PointsOfInterest\Repository\Queries;

final class ConnectionMock extends Connection
{
    private array $inserted = [];

    public function __construct()
    {
        parent::__construct([], new DriverMock());
    }

    public function executeQuery(
        string $sql,
        array $params = [],
        $types = [],
        ?QueryCacheProfile $qcp = null
    ): Result {
        if ($sql === Queries::INSERT) {
            $this->inserted[] = [
                'name'        => $params[0],
                'xCoordinate' => $params[1],
                'yCoordinate' => $params[2],
            ];
            return new ResultMock($this->inserted);
        }

        if ($sql === Queries::FIND) {
            $result = array_filter($this->inserted, function (array $item) use ($params) {
                return $item['name'] == $params[0]
                    && $item['xCoordinate'] == $params[1]
                    && $item['yCoordinate'] == $params[2];
            });

            return new ResultMock($result ? array_values($result) : []);
        }

        if ($sql === Queries::FIND_ALL) {
            return new ResultMock($this->inserted);
        }

        return new ResultMock();
    }

    public function fetchAllAssociative(string $query, array $params = [], array $types = []): array
    {
        return [];
    }

    public function connect(): void
    {
    }

    public function close(): void
    {
    }

    public function lastInserted(): array
    {
        return end($this->inserted);
    }

    public function deleteAll(): void
    {
        $this->inserted = [];
    }
}
