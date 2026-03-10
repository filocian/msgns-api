<?php

declare(strict_types=1);

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Support\Facades\Queue;
use Src\Shared\Infrastructure\Queue\LaravelQueue;

final class FakeQueueJob
{
	use Queueable;

	/**
	 * @param array<string, mixed> $payload
	 */
	public function __construct(public array $payload = []) {}
}

final class FakeConfiguredQueueJob
{
	use Queueable;

	public function __construct(public int $id) {}
}

describe('LaravelQueue', function () {
	it('dispatches mapped string jobs to the laravel queue', function () {
		Queue::fake();
		config()->set('shared.queue.jobs', ['shared.fake_job' => FakeQueueJob::class]);

		$queue = new LaravelQueue(app(), app(QueueContract::class));
		$queue->dispatch('shared.fake_job', ['payload' => ['id' => 10]]);

		Queue::assertPushed(FakeQueueJob::class, fn (FakeQueueJob $job) => $job->payload === ['id' => 10]);
	});

	it('dispatches mapped configured jobs with queue metadata', function () {
		Queue::fake();
		config()->set('shared.queue.jobs', ['shared.fake_job' => [
			'class' => FakeConfiguredQueueJob::class,
			'connection' => 'redis',
			'queue' => 'shared-jobs',
		]]);

		$queue = new LaravelQueue(app(), app(QueueContract::class));
		$queue->dispatch('shared.fake_job', ['id' => 10]);

		Queue::assertPushed(FakeConfiguredQueueJob::class, function (FakeConfiguredQueueJob $job) {
			return $job->id === 10
				&& $job->connection === 'redis'
				&& $job->queue === 'shared-jobs';
		});
	});

	it('dispatches mapped jobs with delay', function () {
		Queue::fake();
		config()->set('shared.queue.jobs', ['shared.fake_job' => [
			'class' => FakeQueueJob::class,
			'connection' => 'redis',
			'queue' => 'shared-jobs',
		]]);

		$queue = new LaravelQueue(app(), app(QueueContract::class));
		$queue->dispatchAfter('shared.fake_job', 60, ['payload' => ['id' => 20]]);

		Queue::assertPushed(FakeQueueJob::class, function (FakeQueueJob $job) {
			return $job->payload === ['id' => 20]
				&& $job->connection === 'redis'
				&& $job->queue === 'shared-jobs'
				&& $job->delay instanceof \DateTimeInterface
				&& $job->delay->getTimestamp() >= now()->addSeconds(55)->getTimestamp();
		});
	});

	it('fails clearly for unknown logical job names', function () {
		config()->set('shared.queue.jobs', [
			'product.stats.update' => ['class' => FakeQueueJob::class],
			'product.usage.update' => ['class' => FakeConfiguredQueueJob::class],
		]);

		$queue = new LaravelQueue(app(), app(QueueContract::class));

		expect(fn () => $queue->dispatch('shared.unknown_job'))->toThrow(
			\RuntimeException::class,
			'No queue job registered for [shared.unknown_job]. Registered jobs: product.stats.update, product.usage.update.',
		);
	});
});
