<?php

namespace Database\Importer;

use Illuminate\Database\ConnectionInterface;

class ImporterUserModel
{
	protected ConnectionInterface $connection;
	protected array $users;

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
				'account_id' => $user->id,
				'email' => $user->email,
				'password' => $user->password,
				'name' => $user->nombre,
				'user_locale' => $user->idioma_usuario,
				'soft_deleted' => boolval($user->eliminado),
				'soft_deleted_date' => $user->fecha_eliminado,
				'tags' => $user->tags,
				'sellers_tags' => $user->sellers_tags,
				'borrado' => boolval($user->borrado),
				'last_access' => $user->ultima_conexion,
				'contact_email' => $user->email_contacto
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
}