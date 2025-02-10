<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\GHL;

use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\Auth\GhlOAuthService;
use App\Models\GHLContact;
use App\Models\GHLOpportunity;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class GhlService
{
	private string $api_version = '2021-07-28';

	public function __construct(
		private readonly GhlOAuthService $ghlOAuthService,
	) {}

	/**
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function updateOrCreateContact(UserDto $userDto): array
	{
		$this->ghlOAuthService->refreshAccessToken();
		$url = 'https://services.leadconnectorhq.com/contacts/upsert';
		$lang = match ($userDto->default_locale) {
			'en_UK' => 'English',
			'ca_ES' => 'Català',
			'es_ES' => 'Español',
			'fr_FR' => 'Francés',
			'de_DE' => 'Alemán',
			'it_IT' => 'Italiano',
		};
		$data = [
			'email' => $userDto->email,
			'name' => $userDto->name,
			'phone' => $userDto->phone,
			'customFields' => [
				[
					'key' => 'phone',
					'field_value' => $userDto->phone ?? '',
				],
				[
					'key' => 'idioma',
					'field_value' => $lang,
				],
			],
		];

		$response = $this->post($url, $data)->json();

		$ghlId = $response['contact']['id'];
		GHLContact::updateOrCreate([
			'user_id' => $userDto->id,
		], [
			'contact_id' => $ghlId,
		]);

		return $response;
	}

	/**
	 * @param array{pipelineId: string, stageId: string, status: string, name: string} $data
	 *
	 * @throws ConnectionException
	 */
	public function createOrUpdateOpportunity(
		ProductDto|null $productDto,
		array $data,
		string|null $opportunityId = null
	): string {
		$contactId = $productDto->user->getContactId();

		if (!$contactId) {
			$contactId = $this->updateOrCreateContact($productDto->user)['contact']['id'];
		}

		$this->ghlOAuthService->refreshAccessToken();
		$url = 'https://services.leadconnectorhq.com/opportunities/upsert';
		$opportunityData = [
			'pipelineId' => $data['pipelineId'],
			'pipelineStageId' => $data['stageId'],
			'name' => $data['name'],
			'status' => $data['status'],
			'contactId' => $contactId,
		];

		$response = $this->post($url, $opportunityData);

		$ghlId = $response->json()['opportunity']['id'];
		GHLOpportunity::updateOrCreate([
			'product_id' => $productDto->id,
		], [
			'opportunity_id' => $ghlId,
		]);

		return $response->body();
	}

	private function post(string $url, array $data)
	{
		$tokens = $this->ghlOAuthService->retrieveAccessToken();

		return Http::asForm()->withHeaders([
			'Authorization' => 'Bearer ' . $tokens['access_token'],
			'Version' => $this->api_version,
		])->post($url, array_merge($data, ['locationId' => $tokens['location_id']]));
	}
}
