<?php

declare(strict_types=1);

namespace App\Events\Product;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductAssignmentErrorEvent
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * Create a new event instance.
	 */
	public function __construct(public int|null $productId, public int|null $userId, public string|null $message = null)
	{
		//
	}

	/**
	 * Get the channels the event should broadcast on.
	 *
	 * @return array<int, Channel>
	 */
	public function broadcastOn(): array
	{
		return [new PrivateChannel('product-assignment-error'), ];
	}
}
