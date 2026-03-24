<?php

declare(strict_types=1);

describe('Products module architecture', function () {

    // ─── Domain layer — no Illuminate\* ──────────────────────────────────────

    it('does not import Illuminate classes inside Products Domain', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Products/Domain');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Illuminate\\',
                "File {$file->getPathname()} must not import Illuminate classes inside Products Domain"
            );
        }
    });

    // ─── Domain layer — no App\* ─────────────────────────────────────────────

    it('does not import App classes inside Products Domain', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Products/Domain');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use App\\',
                "File {$file->getPathname()} must not import App classes inside Products Domain"
            );
        }
    });

    // ─── Application layer — no Illuminate\* ─────────────────────────────────

    it('does not import Illuminate classes inside Products Application', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Products/Application');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Illuminate\\',
                "File {$file->getPathname()} must not import Illuminate classes inside Products Application"
            );
        }
    });

    // ─── Application layer — no App\* ────────────────────────────────────────

    it('does not import App classes inside Products Application', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Products/Application');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use App\\',
                "File {$file->getPathname()} must not import App classes inside Products Application"
            );
        }
    });

    // ─── No delete behavior (AC-008) ─────────────────────────────────────────

    it('does not expose any delete command in Products Application', function () {
        $applicationDir = __DIR__ . '/../../src/Products/Application';
        $directory = new RecursiveDirectoryIterator($applicationDir);
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $basename = $file->getBasename('.php');

            expect(str_contains(strtolower($basename), 'delete'))->toBeFalse(
                "File {$file->getPathname()} must not exist — delete commands are not in scope for Phase 1 (AC-008)"
            );
        }
    });

    it('does not expose any delete method on the ProductType entity', function () {
        $content = file_get_contents(__DIR__ . '/../../src/Products/Domain/Entities/ProductType.php');
        assert(is_string($content));

        expect($content)->not->toContain('function delete')
            ->and($content)->not->toContain('function softDelete')
            ->and($content)->not->toContain('function markDeleted');
    });
});
