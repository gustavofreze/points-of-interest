# State transition (absorb, guard, mutate, emit)

Use this when adding an instance command method that absorbs re-application of its own resulting state, guards itself
through a status predicate, moves to the new status, and emits the matching event. The premises are owned by
`php-hex-application-domain` § State machine contract.

```php
<?php

declare(strict_types=1);

// State-transition worked example. Each transition is an instance command method that:
//   1. returns silently when the aggregate is already in the transition's resulting state, so a
//      redelivered command is absorbed by the model itself, never by the handler.
//   2. guards itself by asking the status enum a predicate (cannotBePaid), never comparing cases.
//   3. moves the aggregate to the new status.
//   4. constructs the matching event and emits it through pushEvent. One transition, one fact.
// The method returns void: no flow signal crosses the boundary. This is the eventual shape and
// mutates $this->status in place. The immutable shape returns a new instance through the private
// constructor. A transition takes only the primitives or domain value objects it genuinely needs
// (Reason) and builds any other value object internally.

public function pay(): void
{
    if ($this->status === PaymentStatus::PAID) {
        return;
    }

    if ($this->status->cannotBePaid()) {
        throw new InvalidPaymentStatusTransition();
    }

    $this->status = PaymentStatus::PAID;

    $event = new PaymentPaid(
        id: $this->id,
        charge: $this->charge,
        createdAt: $this->createdAt
    );
    
    $this->pushEvent(event: $event);
}

public function refund(Reason $reason): void
{
    if ($this->status === PaymentStatus::REFUNDED) {
        return;
    }

    if ($this->status->cannotBeRefunded()) {
        throw new InvalidPaymentStatusTransition();
    }

    $this->status = PaymentStatus::REFUNDED;

    $event = new PaymentRefunded(
        id: $this->id,
        charge: $this->charge,
        reason: $reason,
        createdAt: $this->createdAt
    );
    
    $this->pushEvent(event: $event);
}
```
