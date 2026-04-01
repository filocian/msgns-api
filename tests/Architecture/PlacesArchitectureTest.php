<?php

declare(strict_types=1);

describe('Places module architecture', function () {
	it('does not import Illuminate classes inside Places Domain', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Places/Domain');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());
			assert(is_string($content));

			expect($content)->not->toContain(
				'use Illuminate\\',
				"File {$file->getPathname()} must not import Illuminate classes inside Places Domain"
			);
		}
	});

	it('does not import App classes inside Places Domain', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Places/Domain');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());
			assert(is_string($content));

			expect($content)->not->toContain(
				'use App\\',
				"File {$file->getPathname()} must not import App classes inside Places Domain"
			);
		}
	});

	it('does not import Illuminate classes inside Places Application', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Places/Application');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());
			assert(is_string($content));

			expect($content)->not->toContain(
				'use Illuminate\\',
				"File {$file->getPathname()} must not import Illuminate classes inside Places Application"
			);
		}
	});

	it('does not import App classes inside Places Application', function () {
		$directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Places/Application');
		$iterator = new RecursiveIteratorIterator($directory);

		foreach ($iterator as $file) {
			if (! $file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$content = file_get_contents($file->getPathname());
			assert(is_string($content));

			expect($content)->not->toContain(
				'use App\\',
				"File {$file->getPathname()} must not import App classes inside Places Application"
			);
		}
	});

	it('keeps the adapter and controller final', function () {
		$adapter = file_get_contents(__DIR__ . '/../../src/Places/Infrastructure/Adapters/GooglePlacesAdapter.php');
		$controller = file_get_contents(__DIR__ . '/../../src/Places/Infrastructure/Http/Controllers/PlaceSearchController.php');
		assert(is_string($adapter));
		assert(is_string($controller));

		expect($adapter)->toContain('final class GooglePlacesAdapter')
			->and($controller)->toContain('final class PlaceSearchController');
	});
});
