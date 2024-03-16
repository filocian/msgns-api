<?php

namespace App\Usecases\NFC;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Repositories\NFCRepository;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;

class ActivateNFCUseCase implements UseCaseContract
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
     * @throws AuthenticationException
     */
    public function run(array $data, mixed $opts = null)
    {
        $userId = $this->authService->id();
        if (!$userId) {
            throw new AuthenticationException();
        }

        if (!isset($data['nfcId'])) {
            throw new \Exception('invalid_nfc_id');
        }

        return $this->NFCRepository
            ->update($data['nfcId'], [
                'user_id' => $userId,
                'active' => true
            ]);

    }
}
