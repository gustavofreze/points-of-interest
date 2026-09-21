# Domain event

Use this when adding a domain event the aggregate constructs and emits, carrying value objects and identifiers and
declaring its status and eventType.

```php
<?php

declare(strict_types=1);

// Domain event skeleton. A final readonly class implementing the context event interface
// (<Context>Event, which extends the Commons DomainEvent), using the Commons event-behavior trait
// (DomainEventBehavior), and carrying value objects and aggregate identifiers only. It declares the
// mandatory status() and eventType(). A reasoned event implements the reasoned variant of the
// interface and adds reason() returning the carried Reason. The aggregate constructs and emits it,
// callers never do.

final readonly class <Event> implements <Context>ReasonedEvent
{
    use DomainEventBehavior;

    public function __construct(
        public <Aggregate>Id $id,
        public <Reason> $reason,
        public Instant $createdAt
    ) {
    }

    public function reason(): ?<Reason>
    {
        return $this->reason;
    }

    public function status(): <Status>
    {
        return <Status>::<CASE>;
    }

    public function eventType(): string
    {
        return '<Event>';
    }
}
```
