<?php

declare(strict_types=1);

use Src\Products\Domain\Contracts\ProductRedirectionStrategy;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Errors\ProductMisconfigured;
use Src\Products\Domain\Services\Redirection\AbstractSimpleRedirectionResolver;
use Src\Products\Domain\Services\Redirection\CompositeRedirectionStrategy;
use Src\Products\Domain\Services\Redirection\FacebookRedirectionResolver;
use Src\Products\Domain\Services\Redirection\GoogleRedirectionResolver;
use Src\Products\Domain\Services\Redirection\InfoRedirectionResolver;
use Src\Products\Domain\Services\Redirection\InstagramRedirectionResolver;
use Src\Products\Domain\Services\Redirection\TikTokRedirectionResolver;
use Src\Products\Domain\Services\Redirection\YouTubeRedirectionResolver;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\RedirectionContext;
use Src\Products\Domain\ValueObjects\SimpleRedirectionModel;
use Src\Shared\Core\Errors\NotFound;

function redirectionProduct(string $model, ?string $targetUrl = 'https://example.com'): Product
{
    return Product::fromPersistence(
        id: 1,
        productTypeId: 1,
        userId: 7,
        model: $model,
        linkedToProductId: null,
        password: 'secret',
        targetUrl: $targetUrl,
        usage: 0,
        name: 'Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from(ConfigurationStatus::COMPLETED),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('AbstractSimpleRedirectionResolver', function () {
    it('supports only its configured model and resolves target urls', function () {
        $resolver = new class extends AbstractSimpleRedirectionResolver
        {
            protected function supportedModel(): SimpleRedirectionModel
            {
                return SimpleRedirectionModel::GOOGLE;
            }
        };

        expect($resolver->supports(redirectionProduct('google')))->toBeTrue()
            ->and($resolver->supports(redirectionProduct('instagram')))->toBeFalse()
            ->and($resolver->resolve(redirectionProduct('google'), new RedirectionContext('en'))->url)->toBe('https://example.com');
    });

    it('throws when target url is missing', function () {
        $resolver = new class extends AbstractSimpleRedirectionResolver
        {
            protected function supportedModel(): SimpleRedirectionModel
            {
                return SimpleRedirectionModel::GOOGLE;
            }
        };

        expect(fn () => $resolver->resolve(redirectionProduct('google', null), new RedirectionContext('en')))
            ->toThrow(ProductMisconfigured::class);
    });
});

describe('Concrete simple resolvers', function () {
    it('support only their own models', function (ProductRedirectionStrategy $resolver, string $supported, string $unsupported) {
        expect($resolver->supports(redirectionProduct($supported)))->toBeTrue()
            ->and($resolver->supports(redirectionProduct($unsupported)))->toBeFalse();
    })->with([
        [new GoogleRedirectionResolver(), 'google', 'instagram'],
        [new InstagramRedirectionResolver(), 'instagram', 'google'],
        [new YouTubeRedirectionResolver(), 'youtube', 'google'],
        [new TikTokRedirectionResolver(), 'tiktok', 'google'],
        [new FacebookRedirectionResolver(), 'facebook', 'google'],
        [new InfoRedirectionResolver(), 'info', 'google'],
    ]);
});

describe('CompositeRedirectionStrategy', function () {
    it('delegates to the first supporting resolver in order', function () {
        $first = Mockery::mock(ProductRedirectionStrategy::class);
        $second = Mockery::mock(ProductRedirectionStrategy::class);
        $product = redirectionProduct('google');

        $first->shouldReceive('supports')->once()->with($product)->andReturn(true);
        $first->shouldReceive('resolve')->once()->andReturnUsing(static fn (): mixed => \Src\Products\Domain\ValueObjects\RedirectionTarget::externalUrl('https://first.com'));
        $second->shouldNotReceive('supports');

        $strategy = new CompositeRedirectionStrategy([$first, $second]);

        expect($strategy->resolve($product, new RedirectionContext('en'))->url)->toBe('https://first.com');
    });

    it('throws when no strategy supports the product', function () {
        $strategy = new CompositeRedirectionStrategy([]);

        expect(fn () => $strategy->resolve(redirectionProduct('whatsapp'), new RedirectionContext('en')))
            ->toThrow(NotFound::class);
    });
});
