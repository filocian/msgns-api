<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Appliers;

use InvalidArgumentException;
use Src\Ai\Domain\DataTransferObjects\AiResponseRecord;
use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\GoogleBusiness\Domain\Errors\GoogleBusinessConnectionNotFound;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessApiPort;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessConnectionRepositoryPort;

/**
 * Publishes an approved AiResponse to Google Business by posting a reply
 * to the originating review via the Google Business Profile API.
 *
 * Ownership of the AiResponseRecord is already enforced by ApplyAiResponseHandler
 * (via user_id scope on the Eloquent lookup) — this applier does NOT re-check.
 */
final class GoogleReviewsAiApplier implements AiResponseApplierPort
{
    public function __construct(
        private readonly GoogleBusinessConnectionRepositoryPort $connections,
        private readonly GoogleBusinessApiPort $api,
    ) {}

    public function supports(string $productType): bool
    {
        return $productType === AiProductType::GOOGLE_REVIEW->value;
    }

    public function apply(AiResponseRecord $record): void
    {
        if ($record->productType !== AiProductType::GOOGLE_REVIEW->value) {
            throw new InvalidArgumentException(sprintf(
                'GoogleReviewsAiApplier cannot apply product_type "%s".',
                $record->productType,
            ));
        }

        $connection = $this->connections->findByUserId($record->userId);

        if ($connection === null) {
            throw GoogleBusinessConnectionNotFound::because('google_business_connection_not_found');
        }

        $accessToken = (string) $connection->access_token;

        if ($connection->isTokenExpired()) {
            $refreshed   = $this->api->refreshAccessToken((string) $connection->refresh_token);
            $accessToken = $refreshed['access_token'];
            $this->connections->updateTokens($record->userId, $accessToken, $refreshed['expires_in']);
        }

        $reviewId = (string) ($record->metadata['review_id'] ?? '');

        if ($reviewId === '') {
            throw new InvalidArgumentException('AiResponseRecord metadata.review_id is required for google_review applier.');
        }

        $replyText = $record->editedContent ?? $record->aiContent;

        $this->api->postReviewReply(
            $accessToken,
            (string) $connection->google_account_id,
            (string) $connection->business_location_id,
            $reviewId,
            $replyText,
        );
    }
}
