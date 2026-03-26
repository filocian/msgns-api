<?php

declare(strict_types=1);

use Src\Products\Infrastructure\Services\AlphanumericPasswordGenerator;

describe('AlphanumericPasswordGenerator', function () {

    it('generates a password of the requested length', function () {
        $generator = new AlphanumericPasswordGenerator();

        expect($generator->generate(12))->toHaveLength(12);
        expect($generator->generate(1))->toHaveLength(1);
        expect($generator->generate(64))->toHaveLength(64);
    });

    it('generates only alphanumeric characters (a-z, A-Z, 0-9) (FR-004)', function () {
        $generator = new AlphanumericPasswordGenerator();

        for ($i = 0; $i < 10; $i++) {
            $password = $generator->generate(50);
            expect($password)->toMatch('/^[a-zA-Z0-9]+$/');
        }
    });

    it('generates different passwords on consecutive calls (randomness)', function () {
        $generator = new AlphanumericPasswordGenerator();

        $passwords = array_map(fn () => $generator->generate(12), range(1, 20));
        $unique = array_unique($passwords);

        // With 62^12 combinations, duplicates in 20 runs is astronomically unlikely
        expect(count($unique))->toBeGreaterThan(15);
    });
});
