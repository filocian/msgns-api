<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Nette\FileNotFoundException;

final class ImportedUserSeeder extends Seeder
{
	private string $table = 'users';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$filePath = database_path('importer/data/users.json');

		if (!file_exists($filePath)) {
			throw new FileNotFoundException();
		}

		$seedFile = file_get_contents(database_path('importer/data/users.json'));
		$accounts = json_decode($seedFile);
		$users = [];

		foreach ($accounts as $account) {
			$users[] = [
				'id' => $account->id,
				'name' => $account->name,
				'email' => $account->email,
				'contact_email' => $account->contact_email,
				'password' => $account->password,
				'default_locale' => $account->default_locale,
				'active' => $account->active,
				'password_reset_required' => true,
				'last_access' => $account->last_access,
				'created_at' => $account->created_at,
				'updated_at' => $account->updated_at
			];
		}

		$chunks = array_chunk($users, 1000);

		DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

		foreach ($chunks as $chunk) {
			User::insert($chunk);
		}

		DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

		$maxId = DB::table($this->table)->max('id');
		DB::statement("ALTER TABLE $this->table AUTO_INCREMENT = " . ($maxId + 1));
	}
}
