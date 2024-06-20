<?php

namespace App\Console\Commands;

use Database\Importer\ImporterProductModel;
use Database\Importer\ImporterUserModel;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PrepareDatabaseMigration extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:prepare-database-migration';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		try {
			// Conéctate a la base de datos SQLite
			$adminDb = DB::connection('migration_admin_db');
			$adminDb->statement("CREATE DATABASE IF NOT EXISTS migration_db");
			$migrationDb = DB::connection('migration_db');

			$this->mountUsersTable($migrationDb);
			$this->mountProductsTable($migrationDb);
			$this->sanitizeMigrationData($migrationDb);

			$users = new ImporterUserModel($migrationDb);
			$this->info($users->normalize()->export());

			$google = new ImporterProductModel($migrationDb, 'google');
			$this->info($google->hasType('sticker')->notOwned()->export());

			$instagram = new ImporterProductModel($migrationDb, 'instagram');
			$this->info($instagram->hasType('sticker')->notOwned()->export());

			$info = new ImporterProductModel($migrationDb, 'info');
			$this->info($info->hasType('sticker')->notOwned()->export());
		} catch (QueryException $e) {
			$this->error('Error al ejecutar la consulta: ' . $e->getMessage());
		} catch (\Exception $e) {
			$this->error('Error: ' . $e->getMessage());
		}
	}

	public function mountProductsTable(ConnectionInterface $connection)
	{
		$this->info("Mounting Products table...");
		$connection->statement("DROP TABLE IF EXISTS nfc");

		$sqlSchemePath = database_path('importer/ProductsTable.sql');
		$sqlDataPath = database_path('sql/Products.sql');
		$connection->unprepared(file_get_contents($sqlSchemePath));

		if (!file_exists($sqlDataPath)) {
			$this->error('El archivo SQL no existe en la ruta especificada: ' . $sqlDataPath);
			return;
		}

		$productsSql = file_get_contents($sqlDataPath);
		$connection->unprepared($productsSql);
		$this->info("Mounted Products table.");
	}

	public function mountUsersTable(ConnectionInterface $connection)
	{
		$this->info("Mounting Users table...");
		$connection->statement("DROP TABLE IF EXISTS users");
		$sqlSchemePath = database_path('importer/UsersTable.sql');
		$connection->unprepared(file_get_contents($sqlSchemePath));

		$usersSqlFilePath = database_path('sql/Users.sql');
		if (!file_exists($usersSqlFilePath)) {
			$this->error('El archivo SQL no existe en la ruta especificada: '. $usersSqlFilePath);
			return;
		}

		$usersSql = file_get_contents($usersSqlFilePath);
		$connection->unprepared($usersSql);
		$this->info("Mounted Users table.");
	}

	public function sanitizeMigrationData(ConnectionInterface $connection)
	{
		$this->info('Sanitizing migration data...');

		$this->info("Removing accounts without products...");
		$this->removeAccountsWithNoProducts($connection);
		$this->info("Removed accounts without products.");

		$this->info("Fixing duplicated users and product ownership...");
		$this->fixDuplicatedUsersAndProductOwnership($connection);
		$this->info("Fixed duplicated users and product ownership.");
	}

	public function fixDuplicatedUsersAndProductOwnership(ConnectionInterface $connection){
		$sql = <<<SQL
				DROP TABLE IF EXISTS `audit_users`;

				CREATE TABLE `audit_users` (
					`old_id` BIGINT,
					`new_id` BIGINT,
					`operation` VARCHAR(50),
					`timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

				DROP TABLE IF EXISTS `audit_nfc`;

				CREATE TABLE `audit_nfc` (
					`nfc_id` BIGINT,
					`old_account_id` BIGINT,
					`new_account_id` BIGINT,
					`operation` VARCHAR(50),
					`timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

				/* Paso 2.1: Encontrar usuarios duplicados y el usuario que se va a conservar
				CREATE TEMPORARY TABLE temporary_user_selection AS
				SELECT id, email
				FROM users u1
				WHERE ultima_conexion = (
					SELECT MAX(ultima_conexion)
					FROM users u2
					WHERE u1.email = u2.email
				);*/

				-- Paso 2.1: Encontrar usuarios duplicados y el usuario que se va a conservar
				CREATE TEMPORARY TABLE temporary_user_selection AS
				SELECT id, email
				FROM users u1
				WHERE id = (
					SELECT id
					FROM users u2
					WHERE u1.email = u2.email
					ORDER BY ultima_conexion DESC, id DESC
					LIMIT 1
				);

				-- Paso 2.2: Seleccionar los usuarios que se van a eliminar
				CREATE TEMPORARY TABLE temporary_user_deletion AS
				SELECT u1.id, u1.email
				FROM users u1
				LEFT JOIN temporary_user_selection u2 ON u1.id = u2.id
				WHERE u2.id IS NULL;

				-- Paso 2.3: Registrar los cambios en la tabla de auditoría de `users`
				INSERT INTO audit_users (old_id, new_id, operation)
				SELECT old_user.id, new_user.id, 'DELETE'
				FROM temporary_user_deletion old_user
				JOIN temporary_user_selection new_user ON old_user.email = new_user.email;

				-- Paso 2.4: Actualizar los productos `nfc` con los nuevos `account_id` y registrar cambios
				INSERT INTO audit_nfc (nfc_id, old_account_id, new_account_id, operation)
				SELECT n.id, old_user.id, new_user.id, 'UPDATE'
				FROM nfc n
				JOIN temporary_user_deletion old_user ON n.account_id = old_user.id
				JOIN temporary_user_selection new_user ON old_user.email = new_user.email;

				UPDATE nfc n
				JOIN temporary_user_deletion old_user ON n.account_id = old_user.id
				JOIN temporary_user_selection new_user ON old_user.email = new_user.email
				SET n.account_id = new_user.id;

				-- Paso 2.5: Eliminar usuarios duplicados
				DELETE FROM users
				WHERE id IN (SELECT id FROM temporary_user_deletion);

				-- Paso 2.6: Limpieza de tablas temporales
				DROP TEMPORARY TABLE IF EXISTS temporary_user_selection;
				DROP TEMPORARY TABLE IF EXISTS temporary_user_deletion;
			SQL;

		$connection->unprepared($sql);
	}

	public function removeAccountsWithNoProducts(ConnectionInterface $connection){
		$sql = <<<SQL
			DELETE FROM `users`
			WHERE id NOT IN (
				SELECT DISTINCT account_id
				FROM `nfc`
			);
		SQL;

		$connection->unprepared($sql);
	}
}