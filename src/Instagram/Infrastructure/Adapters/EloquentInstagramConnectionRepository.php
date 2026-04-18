<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Adapters;

use Src\Instagram\Domain\DataTransferObjects\InstagramConnection;
use Src\Instagram\Domain\Models\UserInstagramConnection;
use Src\Instagram\Domain\Ports\InstagramConnectionRepositoryPort;

final class EloquentInstagramConnectionRepository implements InstagramConnectionRepositoryPort
{
    public function findByUserId(int $userId): ?InstagramConnection
    {
        /** @var UserInstagramConnection|null $model */
        $model = UserInstagramConnection::query()
            ->where('user_id', $userId)
            ->first();

        if ($model === null) {
            return null;
        }

        $expiresAt = $model->expires_at !== null
            ? new \DateTimeImmutable($model->expires_at->toDateTimeString())
            : null;

        return new InstagramConnection(
            userId:            (int) $model->user_id,
            accessToken:       (string) $model->access_token,
            instagramUserId:   (string) $model->instagram_user_id,
            instagramUsername: (string) $model->instagram_username,
            pageId:            (string) $model->page_id,
            expiresAt:         $expiresAt,
        );
    }
}
