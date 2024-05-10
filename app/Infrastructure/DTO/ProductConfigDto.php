<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;

final class ProductConfigDto extends BaseDTO
{
	public string $image_ref;
	public string $password;
	public string|null $google_url;
	public string|null $instagram_url;
	public string|null $facebook_url;
	public string|null $youtube_url;
	public string|null $tiktok_url;
	public string|null $info_url;
	public string|null $whatsapp;

	public function __construct(array $data)
	{
		$this->password = $data['password'];
		$this->image_ref = $data['image_ref'];
		$this->google_url = $data['google_url'] ?? null;
		$this->instagram_url = $data['instagram_url'] ?? null;
		$this->facebook_url = $data['facebook_url'] ?? null;
		$this->youtube_url = $data['youtube_url'] ?? null;
		$this->tiktok_url = $data['tiktok_url'] ?? null;
		$this->info_url = $data['info_url'] ?? null;
		$this->whatsapp = $data['whatsapp'] ?? null;
	}
}
