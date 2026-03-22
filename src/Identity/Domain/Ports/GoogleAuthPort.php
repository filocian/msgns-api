<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Ports;

use Src\Identity\Domain\DTOs\GoogleProfile;

interface GoogleAuthPort
{
    public function getProfile(string $idToken): GoogleProfile;
}
