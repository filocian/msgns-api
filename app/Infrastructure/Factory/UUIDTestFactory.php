<?php
namespace App\Infrastructure\Factory;

use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;

class UUIDTestFactory extends UuidFactory
{
	public UuidInterface $uuid;

	public function uuid4($node = null, ?int $clockSeq = null): UuidInterface
	{
		return $this->uuid;
	}
}