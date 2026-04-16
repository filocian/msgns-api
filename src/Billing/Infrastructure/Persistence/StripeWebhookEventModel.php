<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $stripe_event_id
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class StripeWebhookEventModel extends Model
{
    protected $table = 'stripe_webhook_events';

    /** @var list<string> */
    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'payload',
        'processed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];
}
