# Aggregate root (eventual)

Use this when modeling an eventual aggregate root whose creation factory composes a child entity, generates its own
identity, and emits the past-tense creation event.

```php
<?php

declare(strict_types=1);

// Eventual aggregate root worked example. State is recorded through domain events via a trait
// (EventualAggregateRootBehavior). The creation factory composes a child entity from primitives,
// generates its own typed identity, and emits the past-tense creation event. Identifier generation
// is internal: the factory calls <Aggregate>Id::generate() itself, never taking an id from outside.
// Reconstitution uses the trait's reconstituteStrict(). An immutable aggregate instead exposes a
// value-objects-only from(...) and returns a new instance on every change.

final class Payment implements EventualAggregateRoot
{
    use EventualAggregateRootBehavior;

    private function __construct(
        public PaymentId $id,
        public Charge $charge,
        public PaymentStatus $status,
        public Instant $createdAt
    ) {
    }

    public static function create(
        string $chargeMethod,
        string $chargeCountry,
        int $chargeAmountValue,
        string $chargeAmountCurrency
    ): Payment {
        $charge = Charge::create(
            method: $chargeMethod,
            country: $chargeCountry,
            amountValue: $chargeAmountValue,
            amountCurrency: $chargeAmountCurrency
        );

        $payment = new Payment(
            id: PaymentId::generate(),
            charge: $charge,
            status: PaymentStatus::CREATED,
            createdAt: Instant::now()
        );
        $event = new PaymentCreated(
            id: $payment->id,
            charge: $payment->charge,
            createdAt: $payment->createdAt
        );
        $payment->pushEvent(event: $event);

        return $payment;
    }
}
```
