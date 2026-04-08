<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\WhatsappMessage;
use Src\Products\Domain\Errors\ProductMisconfigured;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;
use Src\Products\Domain\Services\Redirection\WhatsappRedirectionResolver;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\RedirectionContext;

function makeWhatsappProduct(int $id = 1): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: 7,
        model: 'whatsapp',
        linkedToProductId: null,
        password: 'secret',
        targetUrl: null,
        usage: 0,
        name: 'WhatsApp Product',
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

function makeResolvedMessage(
    int $id = 1,
    int $productId = 1,
    int $phoneId = 10,
    int $localeId = 5,
    string $localeCode = 'es_ES',
    string $message = 'Hola, ¿en qué podemos ayudarte?',
    bool $isDefault = true,
    string $phone = '612345678',
    string $prefix = '34',
): WhatsappMessage {
    return WhatsappMessage::fromPersistence(
        id: $id,
        productId: $productId,
        phoneId: $phoneId,
        localeId: $localeId,
        localeCode: $localeCode,
        message: $message,
        isDefault: $isDefault,
        phone: $phone,
        prefix: $prefix,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
    );
}

describe('WhatsappRedirectionResolver', function () {
    it('supports only whatsapp model', function () {
        /** @var MockInterface&WhatsappMessageRepositoryPort $repo */
        $repo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        $resolver = new WhatsappRedirectionResolver($repo);

        expect($resolver->supports(makeWhatsappProduct()))->toBeTrue()
            ->and($resolver->supports(Product::fromPersistence(
                id: 1, productTypeId: 1, userId: 7, model: 'google', linkedToProductId: null,
                password: 'secret', targetUrl: 'https://example.com', usage: 0, name: 'Google Product',
                description: null, active: true, configurationStatus: ConfigurationStatus::from(ConfigurationStatus::COMPLETED),
                assignedAt: null, size: null,
                createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(), deletedAt: null,
            )))->toBeFalse();
    });

    it('resolves using default message when no locale match', function () {
        $product = makeWhatsappProduct();
        $defaultMessage = makeResolvedMessage(
            message: 'Hello, how can we help?',
            localeCode: 'en_US',
            isDefault: true,
            phone: '612345678',
            prefix: '34',
        );

        /** @var MockInterface&WhatsappMessageRepositoryPort $repo */
        $repo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        $repo->shouldReceive('findForResolution')
            ->once()
            ->with(1, 'fr')
            ->andReturn($defaultMessage);

        $resolver = new WhatsappRedirectionResolver($repo);
        $result = $resolver->resolve($product, new RedirectionContext('fr-FR,en;q=0.9'));

        expect($result->url)->toBe('https://api.whatsapp.com/send/?phone=34612345678&text=' . urlencode('Hello, how can we help?'));
    });

    it('resolves using locale-matching message when browser locale matches', function () {
        $product = makeWhatsappProduct();
        $spanishMessage = makeResolvedMessage(
            message: 'Hola, ¿en qué podemos ayudarte?',
            localeCode: 'es_ES',
            isDefault: false,
            phone: '699887766',
            prefix: '34',
        );

        /** @var MockInterface&WhatsappMessageRepositoryPort $repo */
        $repo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        $repo->shouldReceive('findForResolution')
            ->once()
            ->with(1, 'es')
            ->andReturn($spanishMessage);

        $resolver = new WhatsappRedirectionResolver($repo);
        $result = $resolver->resolve($product, new RedirectionContext('es-ES,en;q=0.9'));

        expect($result->url)->toBe('https://api.whatsapp.com/send/?phone=34699887766&text=' . urlencode('Hola, ¿en qué podemos ayudarte?'));
    });

    it('resolves using fallback first message when no locale and no default', function () {
        $product = makeWhatsappProduct();
        $fallbackMessage = makeResolvedMessage(
            message: 'Welcome!',
            localeCode: 'en_US',
            isDefault: false,
            phone: '555000111',
            prefix: '1',
        );

        /** @var MockInterface&WhatsappMessageRepositoryPort $repo */
        $repo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        $repo->shouldReceive('findForResolution')
            ->once()
            ->with(1, null)
            ->andReturn($fallbackMessage);

        $resolver = new WhatsappRedirectionResolver($repo);
        $result = $resolver->resolve($product, new RedirectionContext(''));

        expect($result->url)->toBe('https://api.whatsapp.com/send/?phone=1555000111&text=' . urlencode('Welcome!'));
    });

    it('throws ProductMisconfigured when no messages exist for product', function () {
        $product = makeWhatsappProduct();

        /** @var MockInterface&WhatsappMessageRepositoryPort $repo */
        $repo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        $repo->shouldReceive('findForResolution')
            ->once()
            ->with(1, 'en')
            ->andReturn(null);

        $resolver = new WhatsappRedirectionResolver($repo);

        expect(fn () => $resolver->resolve($product, new RedirectionContext('en')))
            ->toThrow(ProductMisconfigured::class);
    });
});

describe('WhatsappRedirectionResolver::extractPrimaryLanguageCode', function () {
    it('extracts language code from Accept-Language header', function (string $header, ?string $expected) {
        expect(WhatsappRedirectionResolver::extractPrimaryLanguageCode($header))->toBe($expected);
    })->with([
        ['es-ES,en;q=0.9', 'es'],
        ['en', 'en'],
        ['fr-FR', 'fr'],
        ['pt-BR,pt;q=0.9,en;q=0.8', 'pt'],
        ['', null],
        ['  ', null],
    ]);
});
