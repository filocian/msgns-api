<?php

declare(strict_types=1);

describe('Subscriptions module architecture', function () {
    it('does not import Illuminate classes inside Subscriptions Domain', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Subscriptions/Domain');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Illuminate\\',
                "File {$file->getPathname()} must not import Illuminate classes inside Subscriptions Domain",
            );
        }
    });

    it('does not import App classes inside Subscriptions Domain', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Subscriptions/Domain');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use App\\',
                "File {$file->getPathname()} must not import App classes inside Subscriptions Domain",
            );
        }
    });

    it('does not import Illuminate classes inside Subscriptions Application', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Subscriptions/Application');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Illuminate\\',
                "File {$file->getPathname()} must not import Illuminate classes inside Subscriptions Application",
            );
        }
    });

    it('does not import App classes inside Subscriptions Application', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Subscriptions/Application');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use App\\',
                "File {$file->getPathname()} must not import App classes inside Subscriptions Application",
            );
        }
    });

    it('does not import Application classes inside Subscriptions Domain', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Subscriptions/Domain');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Src\\Subscriptions\\Application\\',
                "File {$file->getPathname()} must not import Application classes inside Subscriptions Domain",
            );
        }
    });

    it('does not import Stripe SDK or Cashier inside Subscriptions Domain', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Subscriptions/Domain');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Stripe\\',
                "File {$file->getPathname()} must not import Stripe SDK classes inside Subscriptions Domain",
            );
            expect($content)->not->toContain(
                'use Laravel\\Cashier\\',
                "File {$file->getPathname()} must not import Laravel Cashier classes inside Subscriptions Domain",
            );
        }
    });

    it('does not import Stripe SDK or Cashier inside Subscriptions Application', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Subscriptions/Application');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Stripe\\',
                "File {$file->getPathname()} must not import Stripe SDK classes inside Subscriptions Application",
            );
            expect($content)->not->toContain(
                'use Laravel\\Cashier\\',
                "File {$file->getPathname()} must not import Laravel Cashier classes inside Subscriptions Application",
            );
        }
    });

    it('does not import Stripe SDK or Cashier inside Subscriptions Infrastructure', function () {
        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../../src/Subscriptions/Infrastructure');
        $iterator  = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            assert(is_string($content));

            expect($content)->not->toContain(
                'use Stripe\\',
                "File {$file->getPathname()} must not import Stripe SDK classes inside Subscriptions Infrastructure",
            );
            expect($content)->not->toContain(
                'use Laravel\\Cashier\\',
                "File {$file->getPathname()} must not import Laravel Cashier classes inside Subscriptions Infrastructure",
            );
        }
    });
});
