<?php

declare(strict_types=1);

/**
 * Extract the OpenAPI enum values cited by enum-openapi-audit.md.
 *
 * Usage: php docs/extract-enum-openapi-excerpt.php OPENAPI_JSON [YYYY-MM-DD]
 * OPENAPI_JSON is a local copy of the official OpenAPI JSON. The optional date
 * defaults to the current local date; pass the audit date to reproduce its file.
 * The JSON excerpt is written to standard output.
 */

const SOURCE_URL = 'https://api.shop-pro.jp/v1/spec/open_api.json';

// JSON Pointers identify the compared response fields and request exceptions.
// An empty mapping means the audit compared a description, not an enum constraint.
const ENUM_PATHS = [
    'AuthRedirectUri' => [],
    'AuthScope' => [],
    'CategoryDisplayState' => [
        '/components/schemas/productCategory/properties/display_state',
    ],
    'ContractPlan' => [
        '/components/schemas/shop/properties/contract_plan',
    ],
    'DeliveryChargeFreeType' => [
        '/components/schemas/delivery/properties/charge_free_type',
    ],
    'DeliveryChargeType' => [
        '/components/schemas/delivery/properties/charge_type',
    ],
    'DeliveryMethodType' => [
        '/components/schemas/delivery/properties/method_type',
    ],
    'DisplayState' => [
        '/components/schemas/delivery/properties/display_state',
    ],
    'DomainPlan' => [
        '/components/schemas/shop/properties/domain_plan',
    ],
    'ErrorCode' => [
        '/paths/~1v1~1sales~1{sale_id}~1payment_urls/post/responses/401/content/application~1json/schema/properties/errors/items/properties/code',
    ],
    'ExternalAccountProvider' => [
        '/components/schemas/customer/properties/external_accounts/items/properties/provider',
    ],
    'KouzaType' => [
        '/components/schemas/payment/properties/financial/properties/kouza_type',
    ],
    'MailState' => [
        '/components/schemas/sale/properties/accepted_mail_state',
        '/components/schemas/sale/properties/paid_mail_state',
        '/components/schemas/sale/properties/delivered_mail_state',
        '/paths/~1v1~1sales/get/parameters/11/schema',
        '/paths/~1v1~1sales/get/parameters/12/schema',
        '/paths/~1v1~1sales/get/parameters/13/schema',
    ],
    'MailType' => [
        '/paths/~1v1~1sales~1{sale_id}~1mails/post/requestBody/content/application~1json/schema/properties/mail/properties/type',
    ],
    'OpenState' => [
        '/components/schemas/shop/properties/open_state',
        '/components/schemas/shop/properties/mobile_open_state',
    ],
    'PaymentType' => [
        '/components/schemas/payment/properties/type',
    ],
    'PointState' => [
        '/components/schemas/sale/properties/point_state',
        '/components/schemas/sale/properties/gmo_point_state',
        '/components/schemas/sale/properties/yahoo_point_state',
        '/paths/~1v1~1sales~1{sale_id}/put/requestBody/content/application~1json/schema/properties/sale/properties/point_state',
    ],
    'Prefecture' => [
        '/components/schemas/customer/properties/pref_id',
        '/components/schemas/shop/properties/pref_id',
    ],
    'ProductDisplayState' => [
        '/components/schemas/productGroup/properties/display_state',
        '/components/schemas/productGroupCreateRequest/properties/group/properties/display_state',
        '/components/schemas/productGroupUpdateRequest/properties/group/properties/display_state',
    ],
    'Sex' => [
        '/components/schemas/customer/properties/sex',
        '/components/schemas/sale/properties/customer/properties/sex',
        '/paths/~1v1~1customers/get/parameters/8/schema',
        '/paths/~1v1~1sales/post/requestBody/content/application~1json/schema/allOf/0/properties/sale/properties/customer/properties/sex',
    ],
    'ShopState' => [
        '/components/schemas/shop/properties/state',
    ],
    'TaxRoundingMethod' => [
        '/components/schemas/shop/properties/tax_rounding_method',
    ],
    'TaxType' => [
        '/components/schemas/shop/properties/tax_type',
    ],
];

function atPointer(object $document, string $pointer): mixed
{
    $node = $document;
    foreach (explode('/', ltrim($pointer, '/')) as $encodedPart) {
        $part = str_replace(['~1', '~0'], ['/', '~'], $encodedPart);
        if ($node instanceof stdClass && property_exists($node, $part)) {
            $node = $node->{$part};
        } elseif (is_array($node) && ctype_digit($part) && array_key_exists((int) $part, $node)) {
            $node = $node[(int) $part];
        } else {
            throw new RuntimeException("OpenAPI JSON Pointer does not exist: {$pointer}");
        }
    }

    return $node;
}

function main(array $argv): void
{
    if (count($argv) < 2 || count($argv) > 3) {
        throw new InvalidArgumentException('usage: php docs/extract-enum-openapi-excerpt.php OPENAPI_JSON [YYYY-MM-DD]');
    }

    $raw = @file_get_contents($argv[1]);
    if ($raw === false) {
        throw new RuntimeException("Cannot read OpenAPI JSON: {$argv[1]}");
    }

    $document = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);
    if (!$document instanceof stdClass) {
        throw new RuntimeException('OpenAPI JSON must be an object');
    }

    $entries = new stdClass();
    foreach (ENUM_PATHS as $name => $pointers) {
        $fields = new stdClass();
        foreach ($pointers as $pointer) {
            $field = atPointer($document, $pointer);
            if (!$field instanceof stdClass) {
                throw new RuntimeException("{$pointer} is not an OpenAPI field");
            }
            $values = $field->enum ?? null;
            if ($values !== null && !is_array($values)) {
                throw new RuntimeException("{$pointer}.enum is not an array");
            }
            $fields->{$pointer . '/enum'} = $values;
        }
        $entries->{$name} = $fields;
    }

    $excerpt = [
        'retrieved_at' => $argv[2] ?? date('Y-m-d'),
        'source_url' => SOURCE_URL,
        'source_sha256' => hash('sha256', $raw),
        'entries' => $entries,
    ];
    $json = json_encode($excerpt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    // PHP indents by four spaces; the stored excerpt uses Python's two spaces.
    echo preg_replace_callback('/^( +)/m', static fn (array $match): string => str_repeat(' ', intdiv(strlen($match[1]), 2)), $json) . "\n";
}

try {
    main($argv);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
