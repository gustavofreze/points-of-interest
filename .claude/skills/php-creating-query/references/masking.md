# Output masking

Use when projecting sensitive payer fields into a masked output, with a context-specific masker over the generic
per-field `Mask` primitive.

## Contents

- `PayerMask`: the context-specific masker, dynamic output shape.
- `Mask`: the generic masking primitive carrying the per-field rules.

```php
<?php

declare(strict_types=1);

// The output masking layout. A masked projection is the explicit output of the masking step,
// isolated to one ?array property of the read model (its shape varies by which payer fields are
// present, so it stays an explicit array, never a typed sub-DTO). The context-specific masker
// (<Resource>Mask) lives in Query/<Context>/Shared/Masking/ and composes the generic Mask
// primitive in Query/Shared/Masking/.

// ---------------------------------------------------------------------------------------------
// src/Query/<Context>/Shared/Masking/PayerMask.php  (context-specific masker, dynamic shape)

final readonly class PayerMask
{
    public static function fromJsonOrNull(?string $payerJson): ?array
    {
        if (is_null($payerJson)) {
            return null;
        }

        $decoded = json_decode($payerJson, true);
        $document = ($decoded['document'] ?? null);

        if (!is_array($document)) {
            return null;
        }

        $maskers = [
            'name'       => Mask::name(...),
            'email'      => Mask::email(...),
            'phone'      => Mask::phone(...),
            'birth_date' => Mask::birthDate(...)
        ];

        $masked = [
            'document' => [
                'type'  => strtoupper((string)$document['type']),
                'value' => Mask::document(type: (string)$document['type'], value: (string)$document['value'])
            ]
        ];

        $present = array_filter(
            $maskers,
            static fn(string $field): bool => isset($decoded[$field]),
            ARRAY_FILTER_USE_KEY
        );

        foreach ($present as $field => $masker) {
            $masked[$field] = $masker((string)$decoded[$field]);
        }

        $address = ($decoded['address'] ?? null);

        if (is_array($address)) {
            $masked['address'] = Mask::address(address: $address);
        }

        return $masked;
    }
}

// ---------------------------------------------------------------------------------------------
// src/Query/Shared/Masking/Mask.php  (generic masking primitive, per-field rules)

final readonly class Mask
{
    private const string FIELD_MASK = '***';
    private const string BIRTH_DATE_TEMPLATE = '%s-**-**';
    private const int FIRST_NAME_VISIBLE_LENGTH = 2;
    private const int OTHER_NAME_VISIBLE_LENGTH = 1;
    private const int PHONE_VISIBLE_PREFIX_LENGTH = 5;
    private const int PHONE_VISIBLE_SUFFIX_LENGTH = 4;
    private const int DOCUMENT_VISIBLE_SUFFIX_LENGTH = 2;
    private const int BIRTH_DATE_VISIBLE_PREFIX_LENGTH = 4;
    private const int EMAIL_VISIBLE_LOCAL_PREFIX_LENGTH = 1;
    private const int POSTAL_CODE_VISIBLE_PREFIX_LENGTH = 3;

    public static function name(string $value): string
    {
        $template = '%s***';
        $words = preg_split('/\s+/', trim($value));
        $firstWord = array_shift($words);

        $maskedFirst = sprintf($template, mb_substr((string)$firstWord, 0, self::FIRST_NAME_VISIBLE_LENGTH, 'UTF-8'));
        $maskedOthers = array_map(
            static fn(string $word): string => sprintf(
                $template,
                mb_substr($word, 0, self::OTHER_NAME_VISIBLE_LENGTH, 'UTF-8')
            ),
            $words
        );

        return implode(' ', [$maskedFirst, ...$maskedOthers]);
    }

    public static function email(string $value): string
    {
        $separator = strrpos($value, '@');

        if ($separator === false) {
            return self::FIELD_MASK;
        }

        $template = '%s***%s';
        $local = substr($value, 0, $separator);
        $domain = substr($value, $separator);
        $visiblePrefix = mb_substr($local, 0, self::EMAIL_VISIBLE_LOCAL_PREFIX_LENGTH, 'UTF-8');

        return sprintf($template, $visiblePrefix, $domain);
    }

    public static function phone(string $value): string
    {
        $visibleTotal = (self::PHONE_VISIBLE_PREFIX_LENGTH + self::PHONE_VISIBLE_SUFFIX_LENGTH);
        $hiddenLength = max(0, (strlen($value) - $visibleTotal));

        return substr_replace(
            $value,
            str_repeat('*', $hiddenLength),
            self::PHONE_VISIBLE_PREFIX_LENGTH,
            $hiddenLength
        );
    }

    public static function address(array $address): array
    {
        $masked = [];

        foreach ($address as $field => $value) {
            $masked[$field] = match (true) {
                $field === 'city', $field === 'state' => $value,
                $field === 'postal_code'              => self::postalCode(value: $value),
                default                               => self::FIELD_MASK
            };
        }

        return $masked;
    }

    public static function document(string $type, string $value): string
    {
        $visibleSuffix = substr($value, -self::DOCUMENT_VISIBLE_SUFFIX_LENGTH);

        $template = match ($type) {
            'cpf'   => '***.***.***-%s',
            'cnpj'  => '**.***.***/****-%s',
            default => '***%s'
        };

        return sprintf($template, $visibleSuffix);
    }

    public static function birthDate(string $value): string
    {
        return sprintf(self::BIRTH_DATE_TEMPLATE, substr($value, 0, self::BIRTH_DATE_VISIBLE_PREFIX_LENGTH));
    }

    public static function postalCode(string $value): string
    {
        $remainder = substr($value, self::POSTAL_CODE_VISIBLE_PREFIX_LENGTH);
        $visiblePrefix = substr($value, 0, self::POSTAL_CODE_VISIBLE_PREFIX_LENGTH);
        $maskedRemainder = preg_replace('/[A-Za-z0-9]/', '*', $remainder);

        return $visiblePrefix . $maskedRemainder;
    }
}
```
