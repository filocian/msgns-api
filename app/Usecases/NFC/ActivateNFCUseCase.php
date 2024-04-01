<?php

namespace App\Usecases\NFC;

use App\Exceptions\NFC\NFCNotFoundException;
use App\Exceptions\NFC\NFCNotOwnedException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Repositories\NFCRepository;
use App\Services\AuthService;
use App\Services\NFCService;


class ActivateNFCUseCase implements UseCaseContract
{
    public string $hello = 'fck';

    public function __construct(
        private readonly NFCRepository $NFCRepository,
        private readonly AuthService   $authService,
        private readonly NFCService    $NFCService,

    )
    {
        $this->hello = 'hello';
    }

    /**
     * @throws NFCNotOwnedException
     * @throws NFCNotFoundException
     * @var array{nfcId: int, password: string} $data
     */
    public function run(?array $data = null, ?array $opts = null)
    {
        $userId = $this->authService->id();
        $nfcId = $data['nfcId'];
        $password = $data['password'];

        $nfc = $this->NFCRepository->findOneBy([
            'id' => $nfcId,
            'password' => $password
        ]);

        return $this->NFCRepository
            ->update($data['nfcId'], [
                'user_id' => $userId,
                'active' => true
            ]);
    }
}
