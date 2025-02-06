<?php

declare(strict_types=1);

namespace App\UseCases\Ghl;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\UserDto;
use App\Infrastructure\Services\GHL\GhlService;
use App\Models\GHLContact;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;

final readonly class CreateGHLContactUC implements UseCaseContract
{
	public function __construct(
		private GhlService $ghlService
	) {}

	/**
	 * @param array{user?: User, user_dto?: UserDto} $data
	 * @param array|null $opts
	 * @return UserDto | null
	 * @throws ConnectionException
	 */
	public function run(mixed $data = null, ?array $opts = null): UserDto|null
	{
		$user = $data['user'] ?? null;
		$userDto = $data['user_dto'] ?? null;

		if(!$userDto && $user){
			$user = $data['user'];
			$userDto = UserDto::fromModel($user);
		}

		$response = $this->ghlService->createContact($userDto);
		$ghlId = $response['contact']['id'];
		GHLContact::updateOrCreate([
			'user_id' => $userDto->id,
			'contact_id' => $ghlId
		]);

		return $userDto;
	}
}
