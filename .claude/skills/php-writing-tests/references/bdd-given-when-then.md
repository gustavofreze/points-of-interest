# BDD Given-When-Then shapes

Use these shapes when writing a behavioral test, one happy path and one exception path, each with exactly one @When
step.

```php
<?php

declare(strict_types=1);

/**
 * Given/When/Then shapes. Every test uses @Given / @And / @When / @Then doc comments,
 * exactly one @When per test. For an exception test, @Then (expectException) comes before @When.
 */

// Happy path
public function testPublishEventWhenPaymentIsPaid(): void
{
    /** @Given a paid payment */
    $paidPayment = Payment::create(
        amount: 15000,
        chargeId: '9d9d2a4f-ef5b-4cf9-b9c3-09699eb2cd0a',
        currency: 'BRL',
        organizationId: '8b8b1a3e-de4a-3be8-a8b2-08588da1bc09'
    )->submit(charged: Charged::from(
        chargeId: ChargeId::from(value: 'charge-201'),
        provider: Provider::from(value: 'asaas'),
        pixBrcode: null
    ))->pay();

    /** @When the event is published */
    $this->publisher->publish(payment: $paidPayment);

    /** @Then the event type should be PaymentWasPaid */
    self::assertEquals('PaymentWasPaid', $this->publisher->lastEventType());
}

// Exception path. The @Then expectException precedes the @When.
public function testPayPendingPaymentWhenInvalidPaymentStatusTransition(): void
{
    /** @Given a pending payment */
    $pendingPayment = Payment::create(
        amount: 15000,
        chargeId: '9d9d2a4f-ef5b-4cf9-b9c3-09699eb2cd0a',
        currency: 'BRL',
        organizationId: '8b8b1a3e-de4a-3be8-a8b2-08588da1bc09'
    );

    /** @Then an exception indicating that the payment status transition is not allowed should be thrown */
    $this->expectException(InvalidPaymentStatusTransition::class);

    /** @When trying to pay a payment that has not been routed */
    $pendingPayment->pay();
}
```
