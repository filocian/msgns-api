<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

final readonly class RedirectionTarget
{
    private function __construct(
        public string $url,
        public RedirectionType $type,
    ) {}

    public static function externalUrl(string $url): self
    {
        return new self($url, RedirectionType::EXTERNAL_URL);
    }

    /** Future: used by BraceletRedirectionResolver. */
    public static function frontendRoute(string $url): self
    {
        return new self($url, RedirectionType::FRONTEND_ROUTE);
    }

    /**
     * @return array{url: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'type' => $this->type->value,
        ];
    }

    /**
     * @param array{url: string, type: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            type: RedirectionType::from($data['type']),
        );
    }
}
