<?php

declare(strict_types=1);

describe('User model architecture', function () {
	it('does not declare a parallel EloquentUser model anywhere under src', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src');
		$iterator = new RecursiveIteratorIterator($directory);

		$violations = [];

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());
			assert(is_string($content));

			if (preg_match('/\b(?:final\s+|abstract\s+)?class\s+EloquentUser\b/', $content) === 1) {
				$violations[] = $file->getPathname();
			}
		}

		expect($violations)->toBeEmpty(
			"Found forbidden EloquentUser declarations under src:\n" . implode("\n", $violations)
		);
	});
});
