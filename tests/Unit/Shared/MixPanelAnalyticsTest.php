<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Src\Shared\Infrastructure\Analytics\MixPanelAnalytics;

describe('MixPanelAnalytics', function () {
	it('tracks event through existing mixpanel client', function () {
		$client = \Mockery::mock(Mixpanel::class);
		$client->shouldReceive('track')->once()->with('User Registered', ['id' => '123']);

		$adapter = new MixPanelAnalytics($client, authFactoryWithUser(null));
		$adapter->track('User Registered', ['id' => '123']);
	});

	it('identifies user profile', function () {
		$client = \Mockery::mock(Mixpanel::class);
		$people = \Mockery::mock();
		$client->people = $people;
		$client->shouldReceive('identify')->once()->with('user-1');
		$people->shouldReceive('set')->once()->with('user-1', ['email' => 'john@example.com']);

		$adapter = new MixPanelAnalytics($client, authFactoryWithUser(null));
		$adapter->identify('user-1', ['email' => 'john@example.com']);
	});

	it('handles empty properties', function () {
		$client = \Mockery::mock(Mixpanel::class);
		$client->shouldReceive('track')->once()->with('Heartbeat', []);

		$adapter = new MixPanelAnalytics($client, authFactoryWithUser(null));
		$adapter->track('Heartbeat');
	});

	it('emits legacy info events with authenticated user metadata', function () {
		\config()->set('services.mixpanel.source', 'API');

		$client = \Mockery::mock(Mixpanel::class);
		$client->shouldReceive('track')->once()->withArgs(function (string $event, array $properties) {
			return $event === '[API] [#INFO] => Product Updated'
				&& $properties['user_id'] === '42@USER'
				&& $properties['source'] === 'API'
				&& $properties['severity'] === 'INFO'
				&& $properties['title'] === 'Product title'
				&& $properties['message'] === 'Product updated successfully'
				&& $properties['data'] === ['productId' => 42]
				&& is_string($properties['timestamp']);
		});

		$adapter = new MixPanelAnalytics($client, authFactoryWithUser(authenticatableWithId('42')));
		$adapter->info('Product Updated', 'Product title', 'Product updated successfully', ['productId' => 42]);
	});

	it('uses the configured system alias when there is no authenticated user', function () {
		\config()->set('services.mixpanel.source', 'API');

		$client = \Mockery::mock(Mixpanel::class);
		$client->shouldReceive('track')->once()->withArgs(function (string $event, array $properties) {
			return $event === '[API] [#ERROR] => Product Sync Failed'
				&& $properties['user_id'] === 'SYNC@WORKER'
				&& $properties['severity'] === 'ERROR'
				&& $properties['data'] === ['reason' => 'timeout'];
		});

		$adapter = new MixPanelAnalytics($client, authFactoryWithUser(null));
		$adapter->setSystemAlias('SYNC@WORKER');
		$adapter->error('Product Sync Failed', 'Sync failed', 'The sync job timed out', ['reason' => 'timeout']);
	});

	it('passes group properties through to the mixpanel client', function () {
		$client = \Mockery::mock(Mixpanel::class);
		$group = \Mockery::mock();
		$client->group = $group;
		$group->shouldReceive('set')->once()->with('company', 'acme', ['plan' => 'pro']);

		$adapter = new MixPanelAnalytics($client, authFactoryWithUser(null));
		$adapter->setGroup('company', 'acme', ['plan' => 'pro']);
	});
});

/**
 * Creates an auth factory mock returning the provided user.
 */
function authFactoryWithUser(?Authenticatable $user): AuthFactory
{
	$guard = \Mockery::mock(Guard::class);
	$guard->shouldReceive('user')->andReturn($user);

	$factory = \Mockery::mock(AuthFactory::class);
	$factory->shouldReceive('guard')->andReturn($guard);

	return $factory;
}

/**
 * Creates an authenticatable mock with a fixed identifier.
 */
function authenticatableWithId(string $id): Authenticatable
{
	$user = \Mockery::mock(Authenticatable::class);
	$user->shouldReceive('getAuthIdentifier')->andReturn($id);

	return $user;
}
