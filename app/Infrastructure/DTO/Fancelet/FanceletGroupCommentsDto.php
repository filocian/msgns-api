<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Fancelet;

use App\Infrastructure\Contracts\DTO\Abstract\BaseDTO;

final class FanceletGroupCommentsDto extends BaseDTO
{
	public string $group_id;
	public array $comments;

	public function __construct(string $groupId, array $dynamoDbCommentsResponseItems, array|null $includeTags = null)
	{
		$this->group_id = $groupId;
		$this->comments = [];

		foreach ($dynamoDbCommentsResponseItems as $comment) {
			$authorId = (int) $comment['ProductId']['N'];
			$message = (string) $comment['comment']['S'];
			$timestamp = (string) $comment['Timestamp']['S'];
			$tags = [];

			if ($includeTags) {
				foreach ($includeTags as $tag) {
					if (isset($comment[$tag])) {
						$tags[$tag] = $comment[$tag]['S'];
					}
				}
			}

			$this->comments[] = new FanceletCommentDto($authorId, $message, $timestamp, $tags);
		}
	}
}
