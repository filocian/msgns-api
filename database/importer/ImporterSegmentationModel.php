<?php

declare(strict_types=1);

namespace Database\Importer;

use Illuminate\Database\ConnectionInterface;

final class ImporterSegmentationModel
{
	private ConnectionInterface $connection;
	private array $usersBusiness;

	public function __construct(ConnectionInterface $connection)
	{
		$loadUsersQuery = <<<SQL
				SELECT id, sellers_tags
				FROM users;
			SQL;
		$this->connection = $connection;
		$this->usersBusiness = $this->connection->select($loadUsersQuery);
	}

	public function normalize(): static
	{
		$this->usersBusiness = array_map(function ($user) {
			$sellerTags = $user->sellers_tags;
			$businessTypes = [];

			if (isset($sellerTags) && $sellerTags !== '') {
				$types = explode(',', $sellerTags);
				foreach ($types as $type) {
					$businessType = $this->resolveBusinessType($type);

					if (isset($businessType)) {
						$businessTypes[] = $businessType;
					}
				}
			}
			return [
				'id' => $user->id,
				'businessTypes' => $businessTypes,
			];
		}, $this->usersBusiness);

		return $this;
	}

	public function export(string $fileName = null): string
	{
		$name = $fileName ?? 'segmentation.json';
		$filePath = 'importer/data/' . $name;
		$jsonFilePath = database_path($filePath);
		file_put_contents($jsonFilePath, collect($this->usersBusiness)->toJson());
		return 'Datos exportados a ' . $filePath;
	}

	public function resolveBusinessType($type): ?string
	{
		return match ($type) {
			'bar', 'bubble_tea', 'coffee_shop', 'pub', 'restaurant', 'tea_shop', 'food_truck', 'caterer', 'chef', 'winery', 'icecream_shop' => 'bar_restaurant',
			'hotel', 'hotel_lodging', 'holiday_apartment', 'house_rental_agency', 'apartments', 'boat_rental', 'boat_trip', 'cruise_line', 'travel', 'travel_agency', 'tours', 'camping', 'monument', 'museum', 'tourist_information_center' => 'tourism',
			'health', 'medical_center', 'hospital', 'hospice', 'psychologist', 'psychology', 'physiotherapy', 'chiropractic_center', 'dental_clinic', 'nursing home', 'acupuncture', 'massage_therapist', 'pulmonologist', 'orthopedic', 'audiologist', 'optical_center', 'dermatologist', 'osteopath', 'pathologist', 'pediatric_center', 'pharmacy', 'plastic_surgery', 'podiatry_clinic' => 'health',
			'aesthetic_clinic', 'beautician', 'beauty_salon', 'hair_salon', 'spa', 'wellness_center', 'nail_salon', 'hair_removal', 'cosmetic', 'perfumery', 'barbershop', 'tattoo', 'tattoos', 'piercing', 'yoga' => 'wellness_beauty',
			'car_dealer', 'car_rental', 'car_wash', 'car_accessories', 'car_assistance', 'mechanical_workshop', 'automotive_body_shop', 'motorbike', 'cars', 'motorhome_dealer' => 'automotive',
			'cigarette_shop', 'tobacco', 'smoke', 'cannabis' => 'smoke',
			'construction', 'contractor', 'cleaning', 'electrician', 'painter', 'locksmith', 'pest_control', 'cabinet_maker', 'architect' => 'contractors_maintenance',
			'amusement_park', 'airsoft', 'discotheque', 'cinema', 'theater', 'sports', 'theme_park', 'nightclub', 'events', 'gamming', 'activities', 'adventure', 'dj', 'diving_club', 'music_studio', 'nature', 'yachts', 'zoo', 'escaperoom', 'martial_arts_school', 'wrestling_school', 'culture', 'dance','influencer', 'kids', 'laser_tag_center', 'news', 'party_planning', 'pilates', 'fitness', 'gym', 'pool', 'radio', 'sports_club', 'wedding_venue' => 'entertainment',
			'computer', 'informatic', 'tech', 'software', 'audiovisual', 'programmer', 'telecom', 'tester', 'mail_service', 'whatsapp', 'phone_shop', 'repair_shop' => 'technology',
			'real_estate', 'assisted_housing_installation', 'loan_house' => 'real_estate',
			'taxi', 'transport', 'carrier' => 'transportation',
			'grocery_store', 'supermarket', 'food_beverage', 'bakery', 'pastry_shop', 'liquor_store' => 'food_grocery',
			'gardening', 'furniture', 'decoration', 'home_appliances', 'handicraft', 'crafts', 'graffiti_shop', 'farm', 'fisher', 'appliances', 'flower_shop', 'office', 'storage' => 'home_garden',
			'clothing', 'shoes', 'wedding_dresses', 'footwear_store' => 'apparel_footwear',
			'accountant', 'bank', 'financial_services', 'lawyer', 'tax_advisor', 'insurance_agency', 'insurance_broker', 'insurance_company', 'notary_public', 'legal_services', 'shelter_insurance' => 'financial_legal',
			'industry', 'agriculture', 'energy', 'printing', 'waste', 'wholesaler' => 'industry',
			'school', 'university', 'kindergarten', 'academy', 'language_academy', 'aviation_school', 'academic_camp', 'study_center', 'coaching', 'entrepreneur', 'driving_school', 'education' => 'education',
			'veterinary_clinic', 'pet_shop', 'pet_service', 'pet_breeder', 'dog_walker', 'dog_first_aid' => 'animals',
			'administration', 'association', 'nonprofit_organization', 'public_entities', 'consulting_agency', 'motivational_speaker', 'professional_organizer', 'service', 'adults', 'blog', 'blogger', 'business', 'charity_organization', 'church', 'contactlessmenu', 'design', 'ecology', 'marketing_agency', 'model', 'parking', 'religion', 'security', 'translator', 'witch' => 'other_services',
			'other_stores', 'antiquarian', 'bookstore', 'jewelry', 'secondhand', 'shopping_center', 'shopping_online', 'shopping_retail', 'toy_store', 'warehouse', 'post_office', 'artist', 'photographer', 'armory', 'betting_agency', 'gas_station', 'local_business', 'pawnshop', 'store' => 'other_stores',
			default => null
		};
	}
}
