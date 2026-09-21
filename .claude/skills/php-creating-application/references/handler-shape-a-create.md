# Handler shape A (single aggregate create)

Use this when wiring the default thin create handler that passes command primitives straight to the aggregate factory.

```php
<?php

declare(strict_types=1);

// Shape A. Single aggregate, create. The default thin handler. Pass command primitives straight to the
// aggregate creation factory, persist via the outbound port, return the aggregate or void. The factory
// owns id generation and builds its own value objects, so the handler builds none, generates no id, and
// pre-checks nothing.

// Correct. Primitives go straight to the factory, which builds its own value objects and generates the
// id. No pre-check, because uniqueness is a schema constraint translated by the repository adapter.
public function handle(CreatePayment $command): Payment
{
    $payment = Payment::create(
        chargeMethod: $command->chargeMethod,
        chargeCountry: $command->chargeCountry,
        chargeAmountValue: $command->chargeAmountValue,
        chargeAmountCurrency: $command->chargeAmountCurrency
    );

    $this->payments->save(payment: $payment);

    return $payment;
}

// PROHIBITED. A value object is built up front to feed the factory, and the id is generated outside the
// factory and passed in. The factory takes primitives and owns id generation.
//
// public function handle(CreatePayment $command): Payment
// {
//     $money = Money::of(
//         currency: $command->chargeAmountCurrency,
//         amountInCents: $command->chargeAmountValue
//     );
//
//     $id = PaymentId::generate();
//
//     $payment = Payment::create(
//         id: $id,
//         money: $money,
//         method: $command->chargeMethod,
//         country: $command->chargeCountry
//     );
//
//     $this->payments->save(payment: $payment);
//
//     return $payment;
// }
```
