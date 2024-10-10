<?php

declare(strict_types=1);

namespace App\Helpers;

use Exception;
use Random\RandomException;

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

	public static function obfuscateEmail(string $email): string
	{
		// Split the email into username and domain parts
		[$username, $domain] = explode('@', $email);

		// Calculate the length of the username and domain name
		$username_length = strlen($username);
		$domain_name = explode('.', $domain)[0];
		$domain_length = strlen($domain_name);

		// Calculate how many characters to obfuscate
		$obfuscate_username_length = (int) ceil($username_length / 2);
		$obfuscate_domain_length = (int) ceil($domain_length / 2);

		// Create the obfuscated username
		$obfuscated_username = substr($username, 0, $username_length - $obfuscate_username_length) .
			str_repeat('*', $obfuscate_username_length);

		// Create the obfuscated domain name
		$obfuscated_domain_name = substr($domain_name, 0, $domain_length - $obfuscate_domain_length) .
			str_repeat('*', $obfuscate_domain_length);

		// Get the domain extension (e.g., 'com')
		$domain_extension = substr($domain, strlen($domain_name));

		// Combine the obfuscated username and domain
		$obfuscated_email = $obfuscated_username . '@' . $obfuscated_domain_name . $domain_extension;

		return $obfuscated_email;
	}

	/**
	 * Generate a UUID version 4.
	 *
	 * @return string
	 * @throws Exception|RandomException if unable to generate random bytes.
	 */
	public static function generateUuidV4(): string
	{
		// Genera 16 bytes (128 bits) de datos aleatorios
		$data = random_bytes(16);

		// Configura la versión a 4 (0100)
		$data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
		// Configura los bits de variante a 10xx
		$data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

		// Formatea los bytes en el estándar UUID
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}
