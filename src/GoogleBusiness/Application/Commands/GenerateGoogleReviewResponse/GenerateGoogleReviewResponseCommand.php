<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Commands\GenerateGoogleReviewResponse;

use Src\Shared\Core\Bus\Command;

final readonly class GenerateGoogleReviewResponseCommand implements Command
{
    public function __construct(
        public int $userId,
        public int $productId,
        public string $reviewId,
        public string $reviewText,
        public int $starRating,
    ) {}

    public function commandName(): string
    {
        return 'google_business.generate_review_response';
    }
}
