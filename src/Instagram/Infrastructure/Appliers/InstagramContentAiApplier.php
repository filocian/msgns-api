<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Appliers;

use InvalidArgumentException;
use Src\Ai\Domain\DataTransferObjects\AiResponseRecord;
use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Instagram\Domain\Errors\InstagramApiUnavailable;
use Src\Instagram\Domain\Errors\InstagramConfigurationMissing;
use Src\Instagram\Domain\Errors\InstagramPublishingFailed;
use Src\Instagram\Domain\Ports\InstagramConnectionRepositoryPort;
use Src\Instagram\Domain\Ports\InstagramGraphApiPort;
use Src\Instagram\Domain\Ports\InstagramProductConfigurationPort;
use Src\Shared\Core\Ports\LogPort;

/**
 * Publishes an approved AiResponse of type `instagram_content` to Instagram
 * via the Graph API's two-step container-create + publish flow.
 *
 * Ownership of the AiResponseRecord is already enforced by ApplyAiResponseHandler
 * (via user_id scope on the Eloquent lookup) — this applier does NOT re-check.
 * The `productType` defensive check exists only to guard against a misrouted
 * composite call.
 */
final class InstagramContentAiApplier implements AiResponseApplierPort
{
    public function __construct(
        private readonly InstagramConnectionRepositoryPort $connections,
        private readonly InstagramProductConfigurationPort $productConfig,
        private readonly InstagramGraphApiPort $graphApi,
        private readonly LogPort $log,
    ) {}

    public function supports(string $productType): bool
    {
        return $productType === AiProductType::INSTAGRAM_CONTENT->value;
    }

    public function apply(AiResponseRecord $record): void
    {
        if ($record->productType !== AiProductType::INSTAGRAM_CONTENT->value) {
            throw new InvalidArgumentException(sprintf(
                'InstagramContentAiApplier cannot apply product_type "%s".',
                $record->productType,
            ));
        }

        $imageUrl = $record->metadata['s3_image_url'] ?? null;
        if (! is_string($imageUrl) || $imageUrl === '') {
            throw InstagramConfigurationMissing::because('missing_image_url', [
                'ai_response_id' => $record->id,
            ]);
        }

        $connection = $this->connections->findByUserId($record->userId);
        if ($connection === null || $connection->isExpired()) {
            throw InstagramConfigurationMissing::because('missing_oauth_token', [
                'user_id' => $record->userId,
            ]);
        }

        $accountId = $this->productConfig->getInstagramAccountIdForProduct($record->productId);
        if ($accountId === null) {
            throw InstagramConfigurationMissing::because('missing_instagram_account_id', [
                'product_id' => $record->productId,
            ]);
        }

        $caption = $record->editedContent ?? $record->aiContent;

        try {
            $creation = $this->graphApi->createMediaContainer(
                $accountId,
                $imageUrl,
                $caption,
                $connection->accessToken,
            );
        } catch (InstagramApiUnavailable $e) {
            throw InstagramPublishingFailed::because('container_creation_failed', [
                'reason' => $e->context()['reason'] ?? null,
            ]);
        }

        $creationId = $creation['id'];

        try {
            $this->graphApi->waitForContainerReady(
                $accountId,
                $creationId,
                $connection->accessToken,
            );
        } catch (InstagramApiUnavailable $e) {
            $upstreamReason = (string) ($e->context()['reason'] ?? '');
            $mapped = ($upstreamReason === 'container_timeout' || $upstreamReason === 'connection_failed')
                ? 'container_timeout'
                : 'container_status_error';

            throw InstagramPublishingFailed::because($mapped, [
                'creation_id'    => $creationId,
                'ai_response_id' => $record->id,
                'reason'         => $upstreamReason,
                'status_code'    => $e->context()['status_code'] ?? null,
            ]);
        }

        try {
            $published = $this->graphApi->publishMediaContainer(
                $accountId,
                $creationId,
                $connection->accessToken,
            );
        } catch (InstagramApiUnavailable $e) {
            $this->log->warning('instagram.publish_failed_after_container_ready', [
                'creation_id'    => $creationId,
                'ai_response_id' => $record->id,
                'reason'         => $e->context()['reason'] ?? null,
            ]);

            throw InstagramPublishingFailed::because('publish_failed', [
                'creation_id'    => $creationId,
                'ai_response_id' => $record->id,
            ]);
        }

        $this->log->info('instagram.published', [
            'ai_response_id' => $record->id,
            'media_id'       => $published['id'],
        ]);
    }
}
