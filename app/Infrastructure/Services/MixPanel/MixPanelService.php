<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\MixPanel;

use App\Infrastructure\DTO\MixPanel\MPMessageDto;
use Mixpanel;

final class MixPanelService
{
	private Mixpanel $mixPanel;
	private string $mpToken;

	public function __construct()
	{
		$this->mpToken = env('MIXPANEL_TOKEN', '');
		$this->mixPanel = Mixpanel::getInstance($this->mpToken);
	}

	public function addEvent(MPMessageDto $messageDto): void
	{
		$data = $messageDto->toArray();

		$this->mixPanel->track($messageDto->eventName, $data);
	}
}
