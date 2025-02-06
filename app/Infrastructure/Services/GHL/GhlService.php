<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\GHL;

use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\Auth\GhlOAuthService;
use App\Static\GHL\StaticGHLOpportunities;
use App\UseCases\Ghl\CreateGHLContactUC;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class GhlService
{
	private string $api_version = '2021-07-28';
	public function __construct(
		private readonly GhlOAuthService $ghlOAuthService,
		private readonly CreateGHLContactUC $createGHLContactUC,
	)
	{
	}

	/**
	 * @throws ConnectionException
	 * @throws Exception
	 */
	public function createContact(UserDto $user): array
	{
		$this->ghlOAuthService->refreshAccessToken();
		$url = "https://services.leadconnectorhq.com/contacts/upsert";
		$data = [
			'email' => $user->email,
			'name' => $user->name,
			'customFields' => [
				[
					'key' => 'idioma',
					'field_value' => 'Clingon'
				]
			]
		];

		return $this->post($url, $data)->json();
	}

	/**
	 * @throws ConnectionException
	 */
	public function createProductAssignedOpportunity(ProductDto|null $productDto): string
	{
		$this->ghlOAuthService->refreshAccessToken();
		$url = "https://services.leadconnectorhq.com/opportunities/";
		$data = [
			'pipelineId' => StaticGHLOpportunities::$PRODUCT_PIPELINE_ID,
			'pipelineStageId' => StaticGHLOpportunities::$PRODUCT_ASSIGNED_STAGE_ID,
			'name' => 'Oportunidad - producto asignadoprueba',
			'status' => 'open',
			'contactId' => $productDto->user->getContactId()
		];

		$response = $this->post($url, $data);
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

	public function resolveContactIdFromProductDto(ProductDto $productDto)
	{
		$contactId = $productDto->user->getContactId();
		$userDto = $productDto->user;

		if(!$contactId){
			$userDto = $this->createGHLContactUC->run(['user_dto' => $productDto->user]);
		}

		return $userDto->getContactId();
	}
}
