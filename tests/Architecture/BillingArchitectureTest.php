<?php

declare(strict_types=1);

describe('Billing module architecture', function () {
    it('does not import Illuminate classes inside Billing Domain', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Billing/Domain');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Illuminate\\',
                "File {$file->getPathname()} must not import Illuminate classes inside Billing Domain",
            );
        }
    });

    it('does not import App classes inside Billing Domain', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Billing/Domain');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use App\\',
                "File {$file->getPathname()} must not import App classes inside Billing Domain",
            );
        }
    });

    it('does not import Illuminate classes inside Billing Application', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Billing/Application');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Illuminate\\',
                "File {$file->getPathname()} must not import Illuminate classes inside Billing Application",
            );
        }
    });

    it('does not import App classes inside Billing Application', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Billing/Application');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use App\\',
                "File {$file->getPathname()} must not import App classes inside Billing Application",
            );
        }
    });

    it('does not import Stripe SDK or Cashier inside Billing Domain', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Billing/Domain');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Stripe\\',
                "File {$file->getPathname()} must not import Stripe SDK classes inside Billing Domain",
            );
            expect($content)->not->toContain(
                'use Laravel\\Cashier\\',
                "File {$file->getPathname()} must not import Laravel Cashier classes inside Billing Domain",
            );
        }
    });

    it('does not import Stripe SDK or Cashier inside Billing Application', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Billing/Application');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Stripe\\',
                "File {$file->getPathname()} must not import Stripe SDK classes inside Billing Application",
            );
            expect($content)->not->toContain(
                'use Laravel\\Cashier\\',
                "File {$file->getPathname()} must not import Laravel Cashier classes inside Billing Application",
            );
        }
    });
});
