<?php

declare(strict_types=1);

describe('Shared core architecture', function () {
	it('does not import Illuminate classes inside Shared Core', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Shared/Core');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());

			expect($content)->not->toContain('use Illuminate\\');
		}
	});
});
