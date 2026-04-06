<?php

declare(strict_types=1);

use Tests\Support\LegacyTestInventory;

$testsRoot = dirname(__DIR__, 2) . '/tests';

require_once $testsRoot . '/Support/LegacyTestInventory.php';

$inventory = LegacyTestInventory::files();
$errors = [];

if ($inventory !== array_values(array_unique($inventory))) {
    $errors[] = 'Legacy test inventory contains duplicate entries.';
}

$sortedInventory = $inventory;
sort($sortedInventory);

if ($inventory !== $sortedInventory) {
    $errors[] = 'Legacy test inventory must stay alphabetically sorted.';
}

foreach ($inventory as $relativePath) {
    $absolutePath = $testsRoot . '/' . $relativePath;

    if (! is_file($absolutePath)) {
        $errors[] = sprintf('Legacy inventory entry does not exist: %s', $relativePath);
        continue;
    }

    $contents = file_get_contents($absolutePath);

    if (! is_string($contents) || ! str_contains($contents, "->group('legacy')")) {
        $errors[] = sprintf('Legacy inventory entry is missing an in-file legacy group marker: %s', $relativePath);
    }
}

$legacyNamedFiles = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsRoot));

foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    if (! str_ends_with($file->getFilename(), 'Test.php')) {
        continue;
    }

    $relativePath = str_replace($testsRoot . '/', '', $file->getPathname());

    if (str_contains($file->getFilename(), 'Legacy')) {
        $legacyNamedFiles[] = $relativePath;
    }
}

sort($legacyNamedFiles);

foreach ($legacyNamedFiles as $relativePath) {
    if (! in_array($relativePath, $inventory, true)) {
        $errors[] = sprintf('Legacy-named test file must be registered in the legacy inventory: %s', $relativePath);
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Legacy test inventory verification failed:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf('Legacy test inventory OK (%d files).%s', count($inventory), PHP_EOL));
