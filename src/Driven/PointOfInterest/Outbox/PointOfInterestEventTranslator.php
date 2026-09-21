<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointOfInterest\Outbox;

use PointsOfInterest\Application\Domain\Events\PointOfInterestEvent;
use PointsOfInterest\Application\Domain\Events\PointOfInterestRegistered as RegisteredFact;
use PointsOfInterest\Driven\PointOfInterest\Outbox\Event\PointOfInterestRegistered;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\IntegrationEvent;
use TinyBlocks\BuildingBlocks\Event\IntegrationEventTranslator;

final readonly class PointOfInterestEventTranslator implements IntegrationEventTranslator
{
    public function supports(EventRecord $record): bool
    {
        return $record->event instanceof PointOfInterestEvent;
    }

    public function translate(EventRecord $record): IntegrationEvent
    {
        /** @var RegisteredFact $event */
        $event = $record->event;

        return new PointOfInterestRegistered(
            name: $event->name->value,
            xCoordinate: $event->coordinates->xCoordinate->value,
            yCoordinate: $event->coordinates->yCoordinate->value
        );
    }
}
