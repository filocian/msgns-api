<?php

namespace App\Usecases\NFC;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Repositories\NFCRepository;
use App\Services\AuthService;


class LoggedUserNFCUseCase implements UseCaseContract
{
    public string $hello = 'fck';

    public function __construct(
        private readonly NFCRepository $NFCRepository,
        private readonly AuthService   $authService,

    )
    {
        $this->hello = 'hello';
    }

    /**
     * @param array|null $data
     * @param array|null $opts
     */
    public function run(?array $data = null, ?array $opts = null): array
    {
        $userId = $this->authService->id();
        return $this->NFCRepository->findBy([
            'user_id' => $userId,
        ]);

    }
}
