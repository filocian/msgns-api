<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Adapters;

use Illuminate\Support\Carbon;
use Src\Instagram\Domain\DataTransferObjects\InstagramConnection;
use Src\Instagram\Domain\Ports\InstagramConnectionRepositoryPort;
use Src\Instagram\Infrastructure\Persistence\UserInstagramConnectionModel;

final class EloquentInstagramConnectionRepository implements InstagramConnectionRepositoryPort
{
    public function findByUserId(int $userId): ?InstagramConnection
    {
        /** @var UserInstagramConnectionModel|null $model */
        $model = UserInstagramConnectionModel::query()
            ->where('user_id', $userId)
            ->first();

        if ($model === null) {
            return null;
        }

        /** @var Carbon|string|null $rawExpiresAt */
        $rawExpiresAt = $model->getAttribute('expires_at');
        if ($rawExpiresAt instanceof Carbon) {
            $expiresAt = new \DateTimeImmutable($rawExpiresAt->toDateTimeString());
        } else {
            $expiresAt = null;
        }

        return new InstagramConnection(
            userId:            (int) $model->getAttribute('user_id'),
            accessToken:       (string) $model->getAttribute('access_token'),
            instagramUserId:   (string) $model->getAttribute('instagram_user_id'),
            instagramUsername: (string) $model->getAttribute('instagram_username'),
            pageId:            (string) $model->getAttribute('page_id'),
            expiresAt:         $expiresAt,
        );
    }
}
