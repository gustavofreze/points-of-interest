# Exception raising behavior shape

Use this shape when testing a domain exception, driving the call path that raises it rather than constructing the
exception directly.

```php
<?php

declare(strict_types=1);

/**
 * Testing a domain exception through the raising behavior. The exception is exercised through the
 * call path that produces it, never by constructing it directly and asserting on its accessors.
 * A dedicated test class for an exception is never justified.
 */

// Prohibited: testing the exception as a value object, no production code is exercised
public function testFromWhenValueGivenThenExposesTheValue(): void
{
    /** @Given an unsupported payment method value */
    $value = 'boleto';

    /** @When the exception is constructed */
    $exception = new UnsupportedPaymentMethod(value: $value);

    /** @Then it exposes the payment method value */
    self::assertSame($value, $exception->value);
}

// Correct: driving the call path that raises the exception
public function testRefundWhenPaymentIsNotRefundableThenThrowsPaymentNotRefundable(): void
{
    /** @Given a refund command for a payment that cannot be refunded */
    $command = new RequestPaymentRefund(
        code: null,
        paymentId: '9d9d2a4f-ef5b-4cf9-b9c3-09699eb2cd0a',
        organizationId: '8b8b1a3e-de4a-3be8-a8b2-08588da1bc09',
        additionalInformation: 'Service not delivered'
    );

    /** @Then an exception indicating the payment cannot be refunded should be thrown */
    $this->expectException(PaymentNotRefundable::class);

    /** @When the command is handled */
    $this->handler->handle(command: $command);
}
```
