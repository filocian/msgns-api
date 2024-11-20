<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\MixPanel;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;

final class MPMessageDto extends BaseDTO
{
	public string $eventName;
	public string $severity;
	public string $user_id;
	public string $timestamp;
	public string $source;
	public string $title;
	public string $message;
	public array|null $data;

	public function __construct(string $eventName, array $metadata, string $severity, string $title, string $message, array|null $data)
	{
		$this->eventName = '[' . $metadata['SOURCE'] . '] [#' . $severity . '] => ' . $eventName;
		$this->user_id = $metadata['DISTINCT_ID'];
		$this->timestamp = $metadata['TIMESTAMP'];
		$this->source = $metadata['SOURCE'];
		$this->severity = $severity;
		$this->title = $title;
		$this->message = $message;
		$this->data = $data;
	}
}
