<?php

namespace App\Services;

use App\DTO\NFCDto;

class NFCService
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

    public function isMine(NFCDto $nfc): bool
    {
        $userId = $this->authService->id();
        if (!$userId) return false;
        return $nfc->user_id === $userId;
    }
}
