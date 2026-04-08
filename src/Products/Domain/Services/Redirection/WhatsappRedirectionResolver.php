<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services\Redirection;

use Src\Products\Domain\Contracts\ProductRedirectionStrategy;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Errors\ProductMisconfigured;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;
use Src\Products\Domain\ValueObjects\RedirectionContext;
use Src\Products\Domain\ValueObjects\RedirectionTarget;

final class WhatsappRedirectionResolver implements ProductRedirectionStrategy
{
    public function __construct(
        private readonly WhatsappMessageRepositoryPort $messageRepository,
    ) {}

    public function supports(Product $product): bool
    {
        return $product->model->value === 'whatsapp';
    }

    public function resolve(Product $product, RedirectionContext $context): RedirectionTarget
    {
        $localePrefix = self::extractPrimaryLanguageCode($context->browserLocales);

        $message = $this->messageRepository->findForResolution($product->id, $localePrefix);

        if ($message === null) {
            throw ProductMisconfigured::missingTargetUrl($product->id);
        }

        $phone = preg_replace('/\D/', '', $message->prefix . $message->phone);
        $text = urlencode($message->message);

        $url = sprintf(
            'https://api.whatsapp.com/send/?phone=%s&text=%s',
            $phone,
            $text,
        );

        return RedirectionTarget::externalUrl($url);
    }

    /**
     * Extract the primary language code from an Accept-Language header.
     * e.g. "es-ES,en;q=0.9" → "es"
     *      "fr" → "fr"
     *      "" → null
     */
    public static function extractPrimaryLanguageCode(string $acceptLanguage): ?string
    {
        $trimmed = trim($acceptLanguage);

        if ($trimmed === '') {
            return null;
        }

        // Take the first language tag (before any comma)
        $firstTag = explode(',', $trimmed)[0];

        // Remove quality factor (;q=...)
        $tag = explode(';', $firstTag)[0];

        // Extract just the language code (before any hyphen)
        $langCode = explode('-', trim($tag))[0];

        $langCode = strtolower(trim($langCode));

        return $langCode !== '' ? $langCode : null;
    }
}
