<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Src\Shared\Infrastructure\Log\LaravelLog;

describe('LaravelLog', function () {
	it('forwards every log level to the psr logger', function () {
		$logger = \Mockery::mock(LoggerInterface::class);
		$logger->shouldReceive('info')->once()->with('info message', ['source' => 'shared']);
		$logger->shouldReceive('warning')->once()->with('warning message', ['source' => 'shared']);
		$logger->shouldReceive('error')->once()->with('error message', ['source' => 'shared']);
		$logger->shouldReceive('debug')->once()->with('debug message', ['source' => 'shared']);

		$log = new LaravelLog($logger);
		$log->info('info message', ['source' => 'shared']);
		$log->warning('warning message', ['source' => 'shared']);
		$log->error('error message', ['source' => 'shared']);
		$log->debug('debug message', ['source' => 'shared']);
	});
});
