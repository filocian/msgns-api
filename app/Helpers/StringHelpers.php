<?php

declare(strict_types=1);

namespace App\Helpers;

final class StringHelpers
{
	public static function generateAlphaNumericString($length): string
	{
		$numDigits = (int) ($length / 4);
		$numUppercase = (int) ($length / 4);
		$numLowercase = $length - $numDigits - $numUppercase;

		$digits = \Illuminate\Support\Str::random($numDigits);
		$uppercase = \Illuminate\Support\Str::random($numUppercase);
		$lowercase = \Illuminate\Support\Str::random($numLowercase);

		$combinedString = $digits . $uppercase . $lowercase;
		return str_shuffle($combinedString);
	}
}
