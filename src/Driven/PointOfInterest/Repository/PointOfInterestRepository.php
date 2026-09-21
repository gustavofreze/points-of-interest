<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointOfInterest\Repository;

use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterest;
use PointsOfInterest\Application\Ports\Outbound\PointsOfInterest;
use PointsOfInterest\Driven\PointOfInterest\Repository\Records\PointOfInterestRecordWriter;
use PointsOfInterest\Driven\Shared\Database\RelationalConnection;
use TinyBlocks\Outbox\OutboxRepository;

final readonly class PointOfInterestRepository implements PointsOfInterest
{
    public function __construct(
        private OutboxRepository $outbox,
        private PointOfInterestRecordWriter $writer,
        private RelationalConnection $connection
    ) {
    }

    public function save(PointOfInterest $pointOfInterest): void
    {
        $this->connection->inTransaction(useCase: function () use ($pointOfInterest): void {
            $this->writer->insert(pointOfInterest: $pointOfInterest);

            $this->outbox->push(records: $pointOfInterest->peekEvents());
        });

        $pointOfInterest->pullEvents();
    }
}
