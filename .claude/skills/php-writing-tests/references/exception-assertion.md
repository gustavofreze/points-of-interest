# Exception assertion shape

Use this shape when asserting on a thrown exception, preferring the expectException family over a try-catch on the
message.

```php
<?php

declare(strict_types=1);

/**
 * Asserting on an exception. Use the expectException* family for class, message, and code.
 * The try/catch form is reserved for accessors PHPUnit cannot reach (getPrevious(), domain accessors).
 */

// Prohibited: try-catch to assert a message
try {
    $service->handle(command: $command);
    self::fail('InvalidPaymentStatusTransition was expected.');
} catch (InvalidPaymentStatusTransition $exception) {
    self::assertStringContainsString('routed', $exception->getMessage());
}

// Correct: PHPUnit's expectExceptionMessage
$this->expectException(InvalidPaymentStatusTransition::class);
$this->expectExceptionMessage('routed');

$service->handle(command: $command);
```
