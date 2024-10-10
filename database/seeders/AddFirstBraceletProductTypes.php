<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddFirstBraceletProductTypes extends Seeder
{
	protected $table = 'product_types';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$now = Carbon::now();
		$types = [];

		// B-BI-01-RN
		$bibleCode = 'B-BI-01-RN';

		$types[] = [
			'code' => $bibleCode,
			'name' => 'Bible',
			'description' => 'Open random content based on the bible',
			'image_ref' => $bibleCode,
			'primary_model' => 'random',
			'secondary_model' => null,
			'created_at' => $now,
			'updated_at' => $now,
		];

		// B-YO-01-2Y
		$yogaCode = 'B-YO-01-2Y';

		$types[] = [
			'code' => $yogaCode,
			'name' => 'Yoga',
			'description' => 'Daily yoga program for registered users',
			'image_ref' => $yogaCode,
			'primary_model' => 'daily_program',
			'secondary_model' => null,
			'created_at' => $now,
			'updated_at' => $now,
		];

		// B-SU-01-1Y
		$successCode = 'B-SU-01-1Y';

		$types[] = [
			'code' => $successCode,
			'name' => 'Success',
			'description' => 'Success Program for registered users',
			'image_ref' => $successCode,
			'primary_model' => 'sequential_program',
			'secondary_model' => null,
			'created_at' => $now,
			'updated_at' => $now,
		];

		// B-LO-01-RN
		$loveCode = 'B-LO-01-RN';

		$types[] = [
			'code' => $loveCode,
			'name' => 'Love',
			'description' => 'Open random content based on love',
			'image_ref' => $loveCode,
			'primary_model' => 'random',
			'secondary_model' => null,
			'created_at' => $now,
			'updated_at' => $now,
		];

		// B-CA-01-2Y
		$calisthenicsCode = 'B-CA-01-2Y';

		$types[] = [
			'code' => $calisthenicsCode,
			'name' => 'Calisthenics',
			'description' => 'Daily Calisthenics program for registered users',
			'image_ref' => $calisthenicsCode,
			'primary_model' => 'daily_program',
			'secondary_model' => null,
			'created_at' => $now,
			'updated_at' => $now,
		];

		// B-SP-01-LN
		$sparksCode = 'B-SP-01-LN';

		$types[] = [
			'code' => $sparksCode,
			'name' => 'Sparks',
			'description' => 'Open links to sparks content',
			'image_ref' => $sparksCode,
			'primary_model' => 'link',
			'secondary_model' => null,
			'created_at' => $now,
			'updated_at' => $now,
		];

		// B-HI-01-1Y
		$hypnosisCode = 'B-HI-01-1Y';

		$types[] = [
			'code' => $hypnosisCode,
			'name' => 'Hypnosis',
			'description' => 'Hypnosis program for registered users',
			'image_ref' => $hypnosisCode,
			'primary_model' => 'sequential_program',
			'secondary_model' => null,
			'created_at' => $now,
			'updated_at' => $now,
		];

		DB::table($this->table)->insert($types);
	}
}
