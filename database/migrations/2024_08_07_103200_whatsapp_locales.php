<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	protected string $table = 'whatsapp_locales';
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create($this->table, function (Blueprint $table) {
			$table->id();
			$table->string('code', 10);
			$table->timestamps();
		});

		$now = Carbon::now()->toDateTimeString();

		DB::table($this->table)->insert([
			['code' => 'es_ES', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ca_ES', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'en_US', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'de_DE', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'fr_FR', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'it_IT', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'nl_NL', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ru_RU', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'hr_HR', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'cs_CZ', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'da_DK', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'fa_IR', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'zh_CN', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'aa_ET', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ab_GE', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'bn_IN', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'et_EE', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'fi_FI', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'bg_BG', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ka_GE', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'bs_BA', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'el_GR', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'he_IL', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'hi_IN', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'hu_HU', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'id_ID', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ar_SA', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ja_JP', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'kk_KZ', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'km_KH', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ko_KR', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'lo_LA', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'lv_LV', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'lt_LT', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'mk_MK', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ms_MY', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'no_NO', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ps_AF', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'pl_PL', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ae_IR', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'pt_PT', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ro_RO', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ak_GH', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'sr_RS', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'sk_SK', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'sl_SI', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'sq_AL', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'am_ET', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'sv_SE', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'tl_PH', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ta_IN', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'th_TH', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'tr_TR', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'uk_UA', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'ur_PK', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'uz_UZ', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'vi_VN', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'eu_ES', 'created_at' => $now, 'updated_at' => $now],
			['code' => 'af_ZA', 'created_at' => $now, 'updated_at' => $now],
		]);
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists($this->table);
	}
};
