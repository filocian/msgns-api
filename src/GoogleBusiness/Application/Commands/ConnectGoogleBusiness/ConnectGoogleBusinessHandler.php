<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Commands\ConnectGoogleBusiness;

use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessConnectionRepositoryPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

final class ConnectGoogleBusinessHandler implements CommandHandler
{
    public function __construct(
        private readonly GoogleBusinessConnectionRepositoryPort $repository,
    ) {}

    public function handle(Command $command): UserGoogleBusinessConnection
    {
        /** @var ConnectGoogleBusinessCommand $command */
        $attributes = [
            'google_account_id'   => $command->googleAccountId,
            'access_token'        => $command->accessToken,
            'token_expires_at'    => now()->addSeconds($command->expiresIn),
            'business_location_id' => $command->businessLocationId,
            'business_name'        => $command->businessName,
        ];

        // Never overwrite refresh_token when Google does not return one.
        if ($command->refreshToken !== null) {
            $attributes['refresh_token'] = $command->refreshToken;
        }

        return $this->repository->upsertForUser($command->userId, $attributes);
    }
}
