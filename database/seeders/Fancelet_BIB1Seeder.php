<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class Fancelet_BIB1Seeder extends Seeder
{
	protected $galleryTable = 'fancelet_content_gallery';
	protected $textsTable = 'fancelet_content_texts';

	private array $verse = [
		91 => [
			'en_EN' => 'Isaiah 6 - Then I heard the voice of the Lord saying, "Whom shall I send, and who will go for us?" And I said, "Here am I; send me!"',
			'es_ES' => 'Isaías 6 - Escuché entonces la voz del Señor que decía: “¿A quién enviaré? ¿Quién irá de parte mía?” Yo le respondí: “Aquí estoy, Señor, envíame”.',
		],
		92 => [
			'en_EN' => 'Psalm 138 - Though the Lord be high, he cares for the lowly; he perceives the haughty from afar.',
			'es_ES' => 'Salmo 138 - Aunque el Señor es excelso, cuida de los humilde, y al altivo reconoce desde lejos.',
		],
		93 => [
			'en_EN' => 'Isaiah 6 - The seraph touched my mouth with it and said: "Now that this has touched your lips, your guilt has departed and your sin is blotted out".',
			'es_ES' => 'Isaías 6 - Con la brasa me tocó la boca, diciéndome: “Mira: Esto ha tocado tus labios. Tu iniquidad ha sido quitada y tus pecados están perdonados”.',
		],
	];

	private array $explanation = [
		91 => [
			'en_EN' => 'This verse highlights the willingness to serve the Lord, showing a heart devoted and ready to fulfill His calling. Responding "Here I am" demonstrates faith and courage to act as an instrument of God’s will.',
			'es_ES' => 'Este versículo resalta la disposición a servir al Señor, mostrando un corazón entregado y dispuesto a cumplir su llamada. Responder "Aquí estoy" demuestra fe y valentía para actuar como instrumento de la voluntad divina.',
		],
		92 => [
			'en_EN' => 'God, despite His greatness, lovingly attends to the humble and recognizes the pride of the arrogant. This psalm invites us to live humbly, trusting in His care, while avoiding the pride that distances us from His grace.',
			'es_ES' => 'Dios, a pesar de su grandeza, atiende con amor a los humildes y reconoce la altivez de los orgullosos. Este salmo nos invita a vivir con humildad, confiando en su cuidado, mientras evitamos el orgullo que nos aleja de su gracia.',
		],
		93 => [
			'en_EN' => 'God purifies and forgives, renewing the soul of those who sincerely approach Him. This act of grace demonstrates His power to transform lives, preparing us to fulfill His purpose with a clean and reconciled heart.',
			'es_ES' => 'Dios purifica y perdona, renovando el alma de quienes se acercan a Él con sinceridad. Este acto de gracia muestra su poder para transformar vidas, preparándonos para cumplir su propósito con un corazón limpio y reconciliado.',
		],
	];

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		DB::table($this->galleryTable)->insert([
			'product_type_id' => 23,
		]);

		$contentId = DB::table($this->galleryTable)
			->where('product_type_id', 23)
			->value('id');



		foreach ($this->verse as $order => $content) {
			$data = [
				'gallery_id' => $contentId,
				'order' => $order,
				'layout_reference' => 'verse',
			];

			foreach ($content as $locale => $text) {
				$data[$locale] = $text;
			}

			DB::table($this->textsTable)->insert($data);
		}

		foreach ($this->explanation as $order => $content) {
			$data = [
				'gallery_id' => $contentId,
				'order' => $order,
				'layout_reference' => 'explanation',
			];

			foreach ($content as $locale => $text) {
				$data[$locale] = $text;
			}

			DB::table($this->textsTable)->insert($data);
		}
	}
}
