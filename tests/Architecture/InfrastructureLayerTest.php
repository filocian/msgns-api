<?php

declare(strict_types=1);

describe('Infrastructure HTTP layer isolation', function () {
    // App\Http\Contracts\Controller — OpenAPI base class all v2 controllers extend
    // App\Http\Requests\* — legacy Form Requests still in use (to be migrated in later phases)
    $allowedImports = [
        'App\Http\Contracts\Controller',
    ];

    $modules = ['Products', 'Identity', 'Places'];

    foreach ($modules as $module) {
        it("does not import App classes inside src/{$module}/Infrastructure/Http except allow-list", function () use ($module, $allowedImports) {
            $baseDir = __DIR__ . "/../../src/{$module}/Infrastructure/Http";

            if (! is_dir($baseDir)) {
                expect(true)->toBeTrue();

                return;
            }

            $directory = new RecursiveDirectoryIterator($baseDir);
            $iterator = new RecursiveIteratorIterator($directory);

            $violations = [];

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                assert(is_string($content));

                preg_match_all('/^use (App\\\\[^;]+);$/m', $content, $matches);

                foreach ($matches[1] as $importedClass) {
                    if (! in_array($importedClass, $allowedImports, true)) {
                        $violations[] = "{$file->getPathname()}: use {$importedClass}";
                    }
                }
            }

            expect($violations)->toBeEmpty(
                "Found forbidden App\\ imports in src/{$module}/Infrastructure/Http:\n" . implode("\n", $violations)
            );
        });
    }
});
