<?php

declare(strict_types=1);

use App\Jobs\GHL\CreateContactJob;
use App\Jobs\GHL\CreateOpportunityJob;
use App\Jobs\GHL\UpdateContactJob;
use App\Jobs\GHL\UpdateOpportunityJob;
use App\Jobs\MixPanel\MixpanelProductAssignedJob;
use App\Jobs\MixPanel\MixpanelProductAssignmentErrorJob;
use App\Jobs\Product\UpdateProductStatsJob;
use App\Jobs\Product\UpdateProductUsageJob;
use Src\Instagram\Application\Jobs\PublishInstagramContentJob;

return [
	'queue' => [
		/*
		|--------------------------------------------------------------------------
		| Logical Queue Job Map
		|--------------------------------------------------------------------------
		|
		| New src/ code dispatches these logical names through QueuePort while the
		| existing Laravel job classes stay in app/ untouched.
		|
		*/
		'jobs' => [
			'ghl.contact.create' => [
				'class' => CreateContactJob::class,
				'connection' => \env('SHARED_QUEUE_CONNECTION'),
				'queue' => \env('SHARED_QUEUE_GHL_QUEUE', \env('SHARED_QUEUE_DEFAULT_QUEUE')),
			],
			'ghl.contact.update' => [
				'class' => UpdateContactJob::class,
				'connection' => \env('SHARED_QUEUE_CONNECTION'),
				'queue' => \env('SHARED_QUEUE_GHL_QUEUE', \env('SHARED_QUEUE_DEFAULT_QUEUE')),
			],
			'ghl.opportunity.create' => [
				'class' => CreateOpportunityJob::class,
				'connection' => \env('SHARED_QUEUE_CONNECTION'),
				'queue' => \env('SHARED_QUEUE_GHL_QUEUE', \env('SHARED_QUEUE_DEFAULT_QUEUE')),
			],
			'ghl.opportunity.update' => [
				'class' => UpdateOpportunityJob::class,
				'connection' => \env('SHARED_QUEUE_CONNECTION'),
				'queue' => \env('SHARED_QUEUE_GHL_QUEUE', \env('SHARED_QUEUE_DEFAULT_QUEUE')),
			],
			'mixpanel.product.assigned' => [
				'class' => MixpanelProductAssignedJob::class,
				'connection' => \env('SHARED_QUEUE_CONNECTION'),
				'queue' => \env('SHARED_QUEUE_MIXPANEL_QUEUE', \env('SHARED_QUEUE_DEFAULT_QUEUE')),
			],
			'mixpanel.product.assignment_failed' => [
				'class' => MixpanelProductAssignmentErrorJob::class,
				'connection' => \env('SHARED_QUEUE_CONNECTION'),
				'queue' => \env('SHARED_QUEUE_MIXPANEL_QUEUE', \env('SHARED_QUEUE_DEFAULT_QUEUE')),
			],
			'product.usage.update' => [
				'class' => UpdateProductUsageJob::class,
				'connection' => \env('SHARED_QUEUE_CONNECTION'),
				'queue' => \env('SHARED_QUEUE_PRODUCT_QUEUE', \env('SHARED_QUEUE_DEFAULT_QUEUE')),
			],
			'product.stats.update' => [
				'class' => UpdateProductStatsJob::class,
				'connection' => \env('SHARED_QUEUE_CONNECTION'),
				'queue' => \env('SHARED_QUEUE_PRODUCT_QUEUE', \env('SHARED_QUEUE_DEFAULT_QUEUE')),
			],
			'instagram.publish' => [
				'class' => PublishInstagramContentJob::class,
				'connection' => \env('SHARED_QUEUE_CONNECTION'),
				'queue' => \env('SHARED_QUEUE_INSTAGRAM_QUEUE', \env('SHARED_QUEUE_DEFAULT_QUEUE')),
			],
		],
	],
];
