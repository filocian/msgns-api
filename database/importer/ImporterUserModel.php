<?php

declare(strict_types=1);

namespace Database\Importer;

use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;

final class ImporterUserModel
{
	private ConnectionInterface $connection;
	private array $users;

	public function __construct(ConnectionInterface $connection)
	{
		$loadUsersQuery = <<<SQL
				SELECT *
				FROM users;
			SQL;
		$this->connection = $connection;
		$this->users = $this->connection->select($loadUsersQuery);
	}

	public function normalize()
	{
		$this->users = array_map(function ($user) {
			return [
				'id' => $user->id,
				'name' => $user->nombre,
				'email' => $this->fixKnownEmailProblems($user->email),
				'contact_email' => $user->email_contacto,
				'phone' => $user->telefono,
				'password' => $user->password,
				'default_locale' => $this->resolveLocale($user->idioma_usuario),
				'active' => boolval($user->activo) && !boolval($user->eliminado),
				'password_reset_required' => true,
				'last_access' => $this->resolveLastAccess($user->ultima_conexion),
				'created_at' => $user->fecha_hora,
				'updated_at' => Carbon::now()->toDateTimeString(),
			];
		}, $this->users);

		return $this;
	}

	public function export(string $fileName = null)
	{
		$name = $fileName ?? 'users.json';
		$filePath = 'importer/data/' . $name;
		$jsonFilePath = database_path($filePath);
		file_put_contents($jsonFilePath, collect($this->users)->toJson());
		return 'Datos exportados a ' . $filePath;
	}

	public function resolveLocale(string $lang): string
	{
		return match ($lang) {
			'ca' => 'ca_ES',
			'es' => 'es_ES',
			'fr' => 'fr_FR',
			'de' => 'de_DE',
			'it' => 'it_IT',

			default => 'en_UK',
		};
	}

	public function resolveLastAccess(string $lastAccess)
	{
		if ($lastAccess === '1970-01-01 00:00:00') {
			$lastAccess = null;
		}

		return $lastAccess;
	}

	public function fixKnownEmailProblems(string $email)
	{
		$wrongDotCom = ['comb', 'comt', 'comm', 'comp', 'don'];
		$wrongDotEs = ['esx'];
		$wrongGmail = ['gnail', 'gamil'];
		$initalEmail = $email;
		$emailParts = explode('@', $email);

		if (count($emailParts) !== 2) {
			return $email;
		}

		$domain = $emailParts[1];
		$domainParts = explode('.', $domain);

		if (count($domainParts) !== 2) {
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
			if (str_contains($domainName, $wrongCase) && strlen($domainName) === 5) {
				$email = str_replace($wrongCase, 'gmail', $email);
				$hasFix = true;
			}
		}

		if ($hasFix) {
			dump($initalEmail . ' -> ' . $email);
		}

		return $email;
	}
}
