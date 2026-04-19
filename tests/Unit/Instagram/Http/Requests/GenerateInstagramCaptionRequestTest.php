<?php

declare(strict_types=1);

use App\Models\Product;
use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Src\Instagram\Infrastructure\Http\Requests\GenerateInstagramCaptionRequest;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(ProductConfigurationStatusSeeder::class));

/**
 * Runs the FormRequest rules against the given payload.
 * Returns the Validator so tests can inspect passes()/fails() + ->errors().
 *
 * @param array<string, mixed> $payload
 */
function validateInstagramCaption(array $payload): \Illuminate\Contracts\Validation\Validator
{
    $request = new GenerateInstagramCaptionRequest();

    return Validator::make($payload, $request->rules());
}

describe('GenerateInstagramCaptionRequest rules', function (): void {

    it('passes validation for a full request with product_id, image_base64, image_mime_type and context', function (): void {
        $product = Product::factory()->create();

        $validator = validateInstagramCaption([
            'product_id'      => $product->id,
            'image_base64'    => base64_encode('image-bytes'),
            'image_mime_type' => 'image/jpeg',
            'context'         => 'Summer collection launch',
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for a text-only request (product_id + context only)', function (): void {
        $product = Product::factory()->create();

        $validator = validateInstagramCaption([
            'product_id' => $product->id,
            'context'    => 'New arrivals',
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for product_id only (context optional)', function (): void {
        $product = Product::factory()->create();

        $validator = validateInstagramCaption([
            'product_id' => $product->id,
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('fails when product_id is missing', function (): void {
        $validator = validateInstagramCaption([
            'context' => 'no product',
        ]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('product_id'))->toBeTrue();
    });

    it('fails when product_id does not exist in the products table', function (): void {
        $validator = validateInstagramCaption([
            'product_id' => 999_999_999,
        ]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('product_id'))->toBeTrue();
    });

    it('fails when image_base64 is provided without image_mime_type', function (): void {
        $product = Product::factory()->create();

        $validator = validateInstagramCaption([
            'product_id'   => $product->id,
            'image_base64' => base64_encode('image-bytes'),
        ]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('image_mime_type'))->toBeTrue();
    });

    it('fails when image_mime_type is provided without image_base64', function (): void {
        $product = Product::factory()->create();

        $validator = validateInstagramCaption([
            'product_id'      => $product->id,
            'image_mime_type' => 'image/jpeg',
        ]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('image_base64'))->toBeTrue();
    });

    it('fails when image_mime_type is not in the allowed list', function (): void {
        $product = Product::factory()->create();

        $validator = validateInstagramCaption([
            'product_id'      => $product->id,
            'image_base64'    => base64_encode('image-bytes'),
            'image_mime_type' => 'image/gif',
        ]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('image_mime_type'))->toBeTrue();
    });

    it('fails when context is longer than 1500 characters', function (): void {
        $product = Product::factory()->create();

        $validator = validateInstagramCaption([
            'product_id' => $product->id,
            'context'    => str_repeat('a', 1501),
        ]);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('context'))->toBeTrue();
    });

    it('accepts context exactly at the 1500 character limit', function (): void {
        $product = Product::factory()->create();

        $validator = validateInstagramCaption([
            'product_id' => $product->id,
            'context'    => str_repeat('a', 1500),
        ]);

        expect($validator->passes())->toBeTrue();
    });

    it('accepts each of the three allowed MIME types', function (string $mime): void {
        $product = Product::factory()->create();

        $validator = validateInstagramCaption([
            'product_id'      => $product->id,
            'image_base64'    => base64_encode('image-bytes'),
            'image_mime_type' => $mime,
        ]);

        expect($validator->passes())->toBeTrue();
    })->with(['image/jpeg', 'image/png', 'image/webp']);
});

describe('GenerateInstagramCaptionRequest::failedValidation', function (): void {

    it('throws HttpResponseException with the shared validation_failed envelope', function (): void {
        $request = new GenerateInstagramCaptionRequest();

        $validator = Validator::make([], $request->rules());

        // Trigger validation — $validator->fails() is true at this point.
        expect($validator->fails())->toBeTrue();

        // Invoke protected failedValidation via Closure::bind to mirror the FormRequest path.
        $invoke = \Closure::bind(
            fn (\Illuminate\Contracts\Validation\Validator $v): never => $this->failedValidation($v),
            $request,
            GenerateInstagramCaptionRequest::class
        );

        try {
            $invoke($validator);
            expect(true)->toBeFalse('Expected HttpResponseException to be thrown');
        } catch (HttpResponseException $e) {
            /** @var \Illuminate\Http\JsonResponse $response */
            $response = $e->getResponse();

            expect($response->getStatusCode())->toBe(422);

            /** @var array{error: array{code: string, context: array{errors: array<string, mixed>}}} $body */
            $body = json_decode((string) $response->getContent(), true);

            expect($body)->toHaveKey('error')
                ->and($body['error']['code'])->toBe('validation_failed')
                ->and($body['error']['context'])->toHaveKey('errors')
                ->and($body['error']['context']['errors'])->toBeArray()
                ->and($body['error']['context']['errors'])->toHaveKey('product_id');
        }
    });
});
