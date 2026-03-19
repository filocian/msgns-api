<?php

declare(strict_types=1);

describe('Identity module architecture', function () {
	it('does not import Illuminate classes inside Identity Domain', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Identity/Domain');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());

			expect($content)->not->toContain(
				'use Illuminate\\',
				"File {$file->getPathname()} must not import Illuminate classes inside Identity Domain"
			);
		}
	});

	it('does not import App classes inside Identity Domain', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Identity/Domain');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());

			expect($content)->not->toContain(
				'use App\\',
				"File {$file->getPathname()} must not import App classes inside Identity Domain"
			);
		}
	});

	it('does not import Illuminate classes inside Identity Application', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Identity/Application');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());

			expect($content)->not->toContain(
				'use Illuminate\\',
				"File {$file->getPathname()} must not import Illuminate classes inside Identity Application"
			);
		}
	});
});
