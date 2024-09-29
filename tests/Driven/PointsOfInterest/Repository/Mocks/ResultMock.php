<?php

namespace PointsOfInterest\Driven\PointsOfInterest\Repository\Mocks;

use Doctrine\DBAL\Result;

class ResultMock extends Result
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function fetchAssociative(): ?array
    {
        return $this->data ? $this->data[0] : null;
    }

    public function fetchAllAssociative(): array
    {
        return $this->data;
    }

    public function rowCount(): int
    {
        return count($this->data);
    }

    public function columnCount(): int
    {
        return $this->data ? count($this->data[0]) : 0;
    }

    public function fetchNumeric(): ?array
    {
        return $this->data ? array_values($this->data[0]) : null;
    }

    public function fetchAllNumeric(): array
    {
        return array_map(fn($row) => array_values($row), $this->data);
    }
}
