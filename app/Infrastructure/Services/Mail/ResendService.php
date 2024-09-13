<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Mail;

use Resend\Laravel\Facades\Resend;

final class ResendService
{
	public function send(
		string $to,
		string $subject = null,
		string $body = null,
		string $from = 'dev@dev.notifications.msgns.rx3d.xyz'
	) {
		Resend::emails()->send([
			'from' => env('MAIL_FROM_ADDRESS'),
			'to' => [$to],
			'subject' => $subject,
			'html' => $body,
		]);
	}
}
