<?php

declare(strict_types=1);

it('documents PATCH /products/{id}/details in OpenAPI with expected contract', function (): void {
    $contents = file_get_contents(base_path('storage/api-docs/api-docs.json'));
    expect($contents)->toBeString();

    /** @var array<string, mixed> $openApi */
    $openApi = json_decode((string) $contents, true, flags: JSON_THROW_ON_ERROR);

    $patch = data_get($openApi, 'paths./products/{id}/details.patch');

    expect($patch)->toBeArray()
        ->and(data_get($patch, 'summary'))->toBe('Partially update product details')
        ->and(data_get($patch, 'requestBody.required'))->toBeTrue();

    expect(data_get($patch, 'requestBody.content.application/json.schema.type'))->toBe('object')
        ->and(data_get($patch, 'requestBody.content.application/json.schema.minProperties'))->toBe(1)
        ->and(data_get($patch, 'requestBody.content.application/json.schema.properties.name.type'))->toBe('string')
        ->and(data_get($patch, 'requestBody.content.application/json.schema.properties.description.type'))->toBe('string')
        ->and(data_get($patch, 'requestBody.content.application/json.schema.properties.description.nullable'))->toBeTrue();

    /** @var list<string> $responseCodes */
    $responseCodes = array_map('strval', array_keys((array) data_get($patch, 'responses', [])));

    expect($responseCodes)
        ->toContain('200', '401', '403', '404', '422');

    expect(data_get($openApi, 'paths./products/{id}/name'))->toBeNull();
});
