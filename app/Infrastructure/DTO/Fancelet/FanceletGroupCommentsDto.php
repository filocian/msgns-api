<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Fancelet;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;

final class FanceletGroupCommentsDto extends BaseDTO
{
	public string $group_id;
	public array $comments;

	public function __construct(string $groupId, array $dynamoDbCommentsResponseItems)
	{
		$this->group_id = $groupId;
		$this->comments = [];

		foreach ($dynamoDbCommentsResponseItems as $comment) {
			$authorId = (int) $comment['ProductId']['N'];
			$message = (string) $comment['comment']['S'];
			$timestamp = (string) $comment['Timestamp']['S'];

			$this->comments[] = new FanceletCommentDto($authorId, $message, $timestamp);
		}
	}
}
