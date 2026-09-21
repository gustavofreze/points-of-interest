<?php

declare(strict_types=1);

namespace Test\Unit\Driven\PointOfInterest\Outbox;

use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\Outbox\OutboxRepository;

final class OutboxRepositorySpy implements OutboxRepository
{
    private int $recordsPushed = 0;

    public function push(EventRecords $records): void
    {
        $this->recordsPushed += $records->count();
    }

    public function recordsPushed(): int
    {
        return $this->recordsPushed;
    }
}
