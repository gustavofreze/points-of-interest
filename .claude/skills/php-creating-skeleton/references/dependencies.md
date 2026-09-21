# Dependencies

Use this shape for the `src/Dependencies.php` composition root, where `definitions()` composes one method per hexagonal
layer through the spread operator. The composition root is declarative wiring only (`php-architecture` § Composition
root): no validation logic and no throwing guard, a precondition belongs to the concrete class it protects.

```php
<?php

declare(strict_types=1);

// src/Dependencies.php composition root. definitions() carries no binding of its own. It composes one
// method per hexagonal layer through the spread operator, in the fixed order shared, driven,
// application, query, driver. Each private static layer method groups the bindings it owns. The
// invariants live in php-architecture (section Composition root).

public static function definitions(): array
{
    return [
        ...self::shared(),
        ...self::driven(),
        ...self::application(),
        ...self::query(),
        ...self::driver()
    ];
}
```
