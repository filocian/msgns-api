<?php

namespace App\Services;

use App\DTO\NFCDto;

class ProductService
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function isVirgin(NFCDto $nfc): bool
    {
        return boolval($nfc->user_id) === false;
    }

    public function isConfigured(NFCDto $nfc): bool
    {
        return boolval($nfc->target_url) && !$this->isVirgin($nfc);
    }

    public function isOwnedBy(NFCDto $nfc, int $userId): bool
    {
        return $nfc->user_id === $userId;
    }

    public function isMine(NFCDto $nfc): bool
    {
        $userId = $this->authService->id();
        return $this->isOwnedBy($nfc, $userId);
    }


}
