<?php

namespace App\Usecases\NFC;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Repositories\NFCRepository;

class FindNFCsCUseCase implements UseCaseContract
{

    public function __construct(
        private readonly NFCRepository $NFCRepository,
    )
    {
    }

    public function run(?array $data = null, array $opts = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->NFCRepository
            ->getAll();
    }
}
