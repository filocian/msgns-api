<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

final class ImportedUserSeeder extends Seeder
{
	private $table = 'users';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$now = Carbon::now();

		if (file_exists(database_path('importer/data/users.json'))) {
			$seedFile = file_get_contents(database_path('importer/data/users.json'));
			$accounts = json_decode($seedFile);
			$users = [];

			foreach ($accounts as $account) {
				$users[] = [
					'migration_id' => $account->account_id,
					'name' => $account->name,
					'email' => $this->fixKnownEmailProblems($account->email),
					'contact_email' => $account->contact_email,
					'password' => $account->password,
					'active' => boolval($account->borrado),
					'default_locale' => $this->resolveLocale($account->user_locale),
					'created_at' => $now,
					'updated_at' => $now,
				];
			}

			$chunks = array_chunk($users, 1000);

			foreach ($chunks as $chunk) {
				User::insert($chunk);
			}
		}
	}

	public function resolveLocale(string $lang): string
	{
		return match ($lang) {
			'ca' => 'ca-ES',
			'es' => 'es-ES',
			'fr' => 'fr-FR',
			'de' => 'de-DE',
			'it' => 'it-IT',

			default => 'en-UK',
		};
	}

	public function fixKnownEmailProblems(string $email)
	{
		$wrongDotCom = ['comb', 'comt', 'comm', 'comp', 'don'];
		$wrongDotEs = ['esx'];
		$wrongGmail = ['gnail', 'gamil'];
		$initalEmail = $email;
		$emailParts = explode('@', $email);

		if (count($emailParts) != 2) {
			return $email;
		}

		$domain = $emailParts[1];
		$domainParts = explode('.', $domain);

		if (count($domainParts) != 2) {
			return $email;
		}

		$domainName = $domainParts[0];
		$domainExtension = $domainParts[1];
		$hasFix = false;

		// Reemplazar errores conocidos en dominios .com
		foreach ($wrongDotCom as $wrongCase) {
			if (str_contains($domainExtension, $wrongCase) && strlen($domainExtension) <= 4) {
				$email = str_replace($wrongCase, 'com', $email);
				$hasFix = true;
			}
		}

		// Reemplazar errores conocidos en dominios .es
		foreach ($wrongDotEs as $wrongCase) {
			if (str_contains($domainExtension, $wrongCase) && strlen($domainExtension) <= 3) {
				$email = str_replace($wrongCase, 'es', $email);
				$hasFix = true;
			}
		}

		// Reemplazar errores conocidos en dominios @gmail
		foreach ($wrongGmail as $wrongCase) {
			if (str_contains($domainName, $wrongCase) && strlen($domainName) == 5) {
				$email = str_replace($wrongCase, 'gmail', $email);
				$hasFix = true;
			}
		}

		if($hasFix) {
			dump($initalEmail . ' -> ' . $email);
		}

		return $email;
	}
}
