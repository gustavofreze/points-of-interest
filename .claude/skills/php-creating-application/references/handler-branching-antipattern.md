# Handler create-or-mutate branching (anti-pattern)

Read this when a handler is tempted to branch on a lookup result, showing the prohibited branch next to the
factory-decides correction.

```php
<?php

declare(strict_types=1);

// Create-or-mutate branching. A handler that calls find... and then chooses between create-new and
// mutate-existing based on whether the result is null is doing domain logic in the wrong layer. A
// factory accepts the optional existing instance and decides, or a domain service owns the decision
// when it is a cross-aggregate rule, with the handler keeping the input and the output. A single
// throwing null-guard is allowed in Shape B. Anything beyond a throwing guard is domain logic.

// PROHIBITED. The handler branches on the lookup result.
public function handle(AuthorizePayment $command): void
{
    $paymentId = PaymentId::from(value: $command->paymentId);
    $existing = $this->payments->findById(paymentId: $paymentId);

    if (is_null($existing)) {
        $payment = Payment::create(
            chargeMethod: $command->chargeMethod,
            chargeCountry: $command->chargeCountry,
            chargeAmountValue: $command->chargeAmountValue,
            chargeAmountCurrency: $command->chargeAmountCurrency
        );
    }

    if (!is_null($existing)) {
        $existing->reauthorize();
        $payment = $existing;
    }

    $this->payments->save(payment: $payment);
}

// CORRECT. A factory accepts the optional existing instance and decides. When the decision is a
// cross-aggregate rule, a domain service owns it instead, with the handler keeping the input and the
// output.
public function handle(AuthorizePayment $command): void
{
    $paymentId = PaymentId::from(value: $command->paymentId);
    $existing = $this->payments->findById(paymentId: $paymentId);

    $payment = Payment::authorize(
        existing: $existing,
        chargeMethod: $command->chargeMethod,
        chargeCountry: $command->chargeCountry,
        chargeAmountValue: $command->chargeAmountValue,
        chargeAmountCurrency: $command->chargeAmountCurrency
    );

    $this->payments->save(payment: $payment);
}
```
