<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Fancelet;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;

final class FanceletCommentDto extends BaseDTO {
	public int $author_id;
	public string $comment;
	public string $timestamp;

	public function __construct(int $author_id, string $comment, string $timestamp) {
		$this->author_id = $author_id;
		$this->comment = $comment;
		$this->timestamp = $timestamp;
	}
}
