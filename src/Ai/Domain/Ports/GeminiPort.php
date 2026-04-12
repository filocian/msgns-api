<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Ports;

use Src\Ai\Domain\DataTransferObjects\AiRequest;
use Src\Ai\Domain\DataTransferObjects\AiResponse;
use Src\Ai\Domain\Errors\GeminiUnavailable;

interface GeminiPort
{
    /**
     * @throws GeminiUnavailable
     */
    public function generate(AiRequest $request): AiResponse;
}
