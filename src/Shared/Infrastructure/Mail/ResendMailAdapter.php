<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Mail;

use App\Infrastructure\Services\Mail\ResendService;
use Src\Shared\Core\Ports\MailPort;

final class ResendMailAdapter implements MailPort
{
    public function __construct(private readonly ResendService $resendService) {}

    public function send(string $to, string $subject, string $html): void
    {
        $this->resendService->send($to, $subject, $html);
    }
}
