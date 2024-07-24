<?php
declare(strict_types=1);


namespace App\Http\Controllers\User;

use App\Http\Contracts\HttpJson;
use App\Http\Requests\Auth\ListRolesRequest;
use App\Http\Requests\Auth\SetUserPasswordRequest;
use App\Http\Requests\User\UserListExportRequest;
use App\Infrastructure\DTO\UserDto;
use App\Models\User;
use App\UseCases\Users\Listing\UserListExportUC;
use App\UseCases\Users\Listing\UserListUC;
use App\UseCases\Users\Search\UserFindByIdUC;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
	public function __construct(
		private readonly UserListUC       $userListUC,
		private readonly UserListExportUC $userListExportUC,
		private readonly UserFindByIdUC   $userFindByIdUC,
	)
	{
	}

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

		return HttpJson::OK(["role_names" => array_map(function ($role){
			return $role["name"];
		}, $roles)]);
	}
}