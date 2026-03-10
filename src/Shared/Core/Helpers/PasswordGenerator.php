<?php

declare(strict_types=1);

namespace Src\Shared\Core\Helpers;

use InvalidArgumentException;

final class PasswordGenerator
{
	private const MIN_LENGTH = 8;
	private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
	private const DIGITS = '0123456789';
	private const SPECIAL = '!@#$%^&*()-_=+[]{}<>?';

	public static function generate(int $length = 16, bool $specialChars = true): string
	{
		if ($length < self::MIN_LENGTH) {
			throw new InvalidArgumentException(sprintf('Password length must be at least %d characters.', self::MIN_LENGTH));
		}

		$requiredPools = [self::UPPERCASE, self::LOWERCASE, self::DIGITS];
		$pool = self::UPPERCASE . self::LOWERCASE . self::DIGITS;

		if ($specialChars) {
			$requiredPools[] = self::SPECIAL;
			$pool .= self::SPECIAL;
		}

		$passwordCharacters = [];

		foreach ($requiredPools as $requiredPool) {
			$passwordCharacters[] = self::randomCharacter($requiredPool);
		}

		while (count($passwordCharacters) < $length) {
			$passwordCharacters[] = self::randomCharacter($pool);
		}

		self::shuffle($passwordCharacters);

		return implode('', $passwordCharacters);
	}

	private static function randomCharacter(string $pool): string
	{
		$maxIndex = strlen($pool) - 1;

		if ($maxIndex < 0) {
			throw new InvalidArgumentException('Character pool cannot be empty.');
		}

		$index = random_int(0, $maxIndex);

		return $pool[$index];
	}

	/**
	 * @param array<int, string> $characters
	 */
	private static function shuffle(array &$characters): void
	{
		for ($index = count($characters) - 1; $index > 0; $index--) {
			$swapIndex = random_int(0, $index);

			[$characters[$index], $characters[$swapIndex]] = [$characters[$swapIndex], $characters[$index]];
		}
	}
}
