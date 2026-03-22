<?php

declare(strict_types=1);

namespace Src\Shared\Core\Ports;

interface MailPort
{
    public function send(string $to, string $subject, string $html): void;
}
