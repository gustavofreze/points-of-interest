<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointOfInterest\Outbox;

use PointsOfInterest\Driven\PointOfInterest\Outbox\Event\PointOfInterestIntegrationEvent;
use TinyBlocks\BuildingBlocks\Event\IntegrationEventRecord;
use TinyBlocks\Mapper\Serializer;
use TinyBlocks\Outbox\Serialization\PayloadSerializer;
use TinyBlocks\Outbox\Serialization\SerializedPayload;

final readonly class PointOfInterestEventPayloadSerializer implements PayloadSerializer
{
    public function __construct(private Serializer $mapper)
    {
    }

    public function supports(IntegrationEventRecord $record): bool
    {
        return $record->event instanceof PointOfInterestIntegrationEvent;
    }

    public function serialize(IntegrationEventRecord $record): SerializedPayload
    {
        return SerializedPayload::fromArray(payload: $this->mapper->toArray(source: $record->event));
    }
}
