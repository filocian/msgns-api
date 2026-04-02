<?php

declare(strict_types=1);

namespace Src\Products\Domain\DataTransfer;

final readonly class GenerationHistorySummaryItem
{
    public function __construct(
        public string $typeCode,
        public string $typeName,
        public int $quantity,
        public ?string $size,
        public ?string $description,
    ) {}

    /**
     * @return array{type_code: string, type_name: string, quantity: int, size: ?string, description: ?string}
     */
    public function toArray(): array
    {
        return [
            'type_code' => $this->typeCode,
            'type_name' => $this->typeName,
            'quantity' => $this->quantity,
            'size' => $this->size,
            'description' => $this->description,
        ];
    }

    /**
     * @param array{type_code: string, type_name: string, quantity: int, size: ?string, description: ?string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            typeCode: $data['type_code'],
            typeName: $data['type_name'],
            quantity: $data['quantity'],
            size: $data['size'],
            description: $data['description'],
        );
    }
}
