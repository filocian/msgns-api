<?php

namespace App\Usecases\NFC;

use App\DTO\NFCDto;
use App\Exceptions\NFC\NFCNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Repositories\NFCRepository;

class FindNFCByIdCUseCase implements UseCaseContract
{
    public string $hello = 'fck';

    public function __construct(
        private readonly NFCRepository $NFCRepository,
    )
    {
        $this->hello = 'hello';
    }

    /**
     * @throws NFCNotFoundException
     */
    public function run(?array $data = null, ?array $opts = null): NFCDto
    {
        if (!isset($data['nfcId'])) {
            throw new \Exception('invalid_nfc_id');
        }

        return $this->NFCRepository
            ->findOne($data['nfcId'], $opts);

    }
}
