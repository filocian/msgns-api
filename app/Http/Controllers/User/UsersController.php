<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\ListRolesRequest;
use App\Http\Requests\User\EditUserDataRequest;
use App\Http\Requests\User\UserListExportRequest;
use App\Infrastructure\DTO\UserDto;
use App\Models\User;
use App\UseCases\Users\Edit\EditUserDataUC;
use App\UseCases\Users\Listing\UserListExportUC;
use App\UseCases\Users\Listing\UserListUC;
use App\UseCases\Users\Search\UserFindByIdUC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Role;

final class UsersController extends Controller
{
	public function __construct(
		private readonly UserListUC $userListUC,
		private readonly UserListExportUC $userListExportUC,
		private readonly UserFindByIdUC $userFindByIdUC,
		private readonly EditUserDataUC $editUserDataUC,
	) {}

	public function list(Request $request): JsonResponse
	{
		$users = $this->userListUC->run($request->all(), $request->all());
		return HttpJson::OK($users->wrapped('users'));
	}

	public function userListExport(UserListExportRequest $request): JsonResponse
	{
		ini_set('memory_limit', '256M');
		$users = $this->userListExportUC->run($request->all(), $request->all());
		return HttpJson::OK(['users' => $users]);
	}

	public function find(Request $request, int $id): JsonResponse
	{
		$users = $this->userFindByIdUC->run(['id' => $id]);
		return HttpJson::OK($users->wrapped('user'));
	}
	public function setUserRoles(Request $request, int $userId): JsonResponse
	{
		// Validate the request data
		$validated = $request->validate([
			'role_names' => 'required|array|min:1',
			'role_names.*' => 'string|exists:roles,name',
		]);

		// Find the user by ID
		$user = User::findOrFail($userId);

		// Sync the roles with the user
		$user->syncRoles($validated['role_names']);

		return HttpJson::OK(UserDto::fromModel($user)->wrapped('user'));
	}

	public function listRoles(ListRolesRequest $request): JsonResponse
	{
		$roles = Role::all()->toArray();

		return HttpJson::OK(['role_names' => array_map(function ($role) {
			return $role['name'];
		}, $roles)]);
	}

	public function updateUserdata(EditUserDataRequest $request, int $userId): JsonResponse
	{
		$data = [
			'user_id' => $userId,
			'email' => $request->input('email'),
			'name' => $request->input('name'),
			'phone' => $request->input('phone') ?? null,
			'country' => $request->input('country') ?? null,
			'default_locale' => $request->input('default_locale') ?? null,
		];

		$user = $this->editUserDataUC->run($data);

		if (!$user) {
			return HttpJson::KO('email_already_in_use');
		}

		return HttpJson::OK($user->wrapped('user'));
	}
}
