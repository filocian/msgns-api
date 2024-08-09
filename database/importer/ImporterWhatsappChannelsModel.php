<?php

namespace Database\Importer;

use Illuminate\Database\ConnectionInterface;

class ImporterWhatsappChannelsModel
{
	protected ConnectionInterface $connection;
	protected array $distinct_product_id;
	protected array $data_by_product;

	public function __construct(ConnectionInterface $connection)
	{
		$distinctProductId = <<<SQL
				SELECT DISTINCT nfc_id
				FROM whatsapp_channels;
			SQL;
		$this->connection = $connection;
		$this->distinct_product_id = $this->connection->select($distinctProductId);
	}

	public function normalize(): static
	{
		$distinctProductPhones = array_map(function ($productId) {
			$productPhonesSql = <<<SQL
				SELECT DISTINCT mobile, mobile_prefix
				FROM whatsapp_channels
				WHERE nfc_id=$productId->nfc_id;
			SQL;

			$productPhonesResult = $this->connection->select($productPhonesSql);

			return [
				'product_id' => $productId->nfc_id,
				'phone_list' => array_map(function ($phone) use ($productId) {
					return [
						'prefix' => $phone->mobile_prefix,
						'phone' => $phone->mobile,
					];
				}, $productPhonesResult),
			];
		}, $this->distinct_product_id);

		$productMessagesByPhone = [];
		foreach ($distinctProductPhones as $productPhones) {
			foreach ($productPhones['phone_list'] as $phone) {
				$productId = $productPhones['product_id'];
				$mobile = $phone['phone'];
				$prefix = $phone['prefix'];

				$productMessagesByPhoneSql = <<<SQL
					SELECT lang_id, text, default_translation, fecha_hora
					FROM whatsapp_channels
					WHERE nfc_id=$productId AND mobile='$mobile' AND mobile_prefix='$prefix';
				SQL;

				$productMessagesByPhoneResult = $this->connection->select($productMessagesByPhoneSql);

				foreach ($productMessagesByPhoneResult as $byPhone) {
					$productMessagesByPhone[] = [
						'product_id' => $productId,
						'prefix' => $prefix,
						'phone' => $mobile,
						'locale' => $this->resolveLocale($byPhone->lang_id),
						'text' => $byPhone->text,
						'default' => boolval($byPhone->default_translation),
						'created_at' => $byPhone->fecha_hora
					];
				}
			}
		}

		$this->data_by_product = array_map(function ($productId) use ($distinctProductPhones, $productMessagesByPhone) {
			$productPhones = array_filter($distinctProductPhones, function ($phone) use ($productId) {
				return $phone['product_id'] == $productId->nfc_id;
			});

			$productMessages = array_filter($productMessagesByPhone, function ($messages) use ($productId) {
				return $messages['product_id'] == $productId->nfc_id;
			});

			$phones = array_shift($productPhones);

			$messages = [];
			foreach ($productMessages as $message) {
				$messages[] = [
					'prefix' => $message['prefix'],
					'phone' => $message['phone'],
					'locale' => $message['locale'],
					'text' => $message['text'],
					'default' => $message['default'],
					'created_at' => $message['created_at']
				];
			}

			return [
				'product_id' => $productId->nfc_id,
				'phones' => $phones['phone_list'],
				'messages' => $messages
			];
		}, $this->distinct_product_id);

		return $this;
	}

	public function export(string $fileName = null): string
	{
		$name = $fileName ?? 'whatsapp_channels.json';
		$filePath = 'importer/data/' . $name;
		$jsonFilePath = database_path($filePath);
		file_put_contents($jsonFilePath, collect($this->data_by_product)->toJson());
		return 'Datos exportados a ' . $filePath;
	}

	private function resolveLocale(int $id): string
	{
		return match ($id) {
			1 => 'es_ES', // Español
			2 => 'ca_ES', // Catalán
			3 => 'en_US', // Inglés
			4 => 'de_DE', // Alemán
			5 => 'fr_FR', // Francés
			6 => 'it_IT', // Italiano
			7 => 'nl_NL', // Neerlandés
			8 => 'ru_RU', // Ruso
			9 => 'hr_HR', // Croata
			10 => 'cs_CZ', // Checo
			11 => 'da_DK', // Danés
			12 => 'fa_IR', // Persa
			13 => 'zh_CN', // Chino
			14 => 'aa_ET', // Afar
			15 => 'ab_GE', // Abjasio
			16 => 'bn_IN', // Bengalí
			17 => 'et_EE', // Estonio
			19 => 'fi_FI', // Finés
			20 => 'bg_BG', // Búlgaro
			22 => 'ka_GE', // Georgiano
			23 => 'bs_BA', // Bosnio
			24 => 'el_GR', // Griego
			25 => 'he_IL', // Hebreo
			26 => 'hi_IN', // Hindi
			27 => 'hu_HU', // Húngaro
			28 => 'id_ID', // Indonesio
			29 => 'ar_SA', // Árabe
			30 => 'ja_JP', // Japonés
			31 => 'kk_KZ', // Kazajo
			32 => 'km_KH', // Jemér
			33 => 'ko_KR', // Coreano
			34 => 'lo_LA', // Lao
			35 => 'lv_LV', // Letón
			36 => 'lt_LT', // Lituano
			37 => 'mk_MK', // Macedonio
			38 => 'ms_MY', // Malayo
			39 => 'no_NO', // Noruego
			40 => 'ps_AF', // Pastún
			41 => 'pl_PL', // Polaco
			42 => 'ae_IR', // Avéstico
			43 => 'pt_PT', // Portugués
			44 => 'ro_RO', // Rumano
			45 => 'ak_GH', // Akan
			46 => 'sr_RS', // Serbio
			47 => 'sk_SK', // Eslovaco
			48 => 'sl_SI', // Esloveno
			49 => 'sq_AL', // Albanés
			50 => 'am_ET', // Amárico
			51 => 'sv_SE', // Sueco
			52 => 'tl_PH', // Tagalo
			53 => 'ta_IN', // Tamil
			54 => 'th_TH', // Tailandés
			55 => 'tr_TR', // Turco
			56 => 'uk_UA', // Ucraniano
			57 => 'ur_PK', // Urdu
			58 => 'uz_UZ', // Uzbeko
			59 => 'vi_VN', // Vietnamita
			60 => 'eu_ES', // Euskera
			61 => 'af_ZA', // Afrikáans
			default => ''
		};
	}
}