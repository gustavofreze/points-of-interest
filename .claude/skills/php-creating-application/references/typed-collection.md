# Typed collection

Use this when adding a typed collection over the domain Collection wrapper, naming the element type in a variadic
factory.

```php
<?php

declare(strict_types=1);

// Typed collection skeleton. Inherits all behavior from the domain Collection wrapper, adding only
// the variadic typed factory that names the element type in its signature. The name is the plural of
// the concept (Payments, Refunds), never a technical suffix like Items, List, Set, or Collection.

final class <Collection> extends Collection
{
    public static function from(<Element> ...$elements): <Collection>
    {
        return <Collection>::createFrom(elements: $elements);
    }
}
```
