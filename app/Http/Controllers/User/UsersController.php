<?php
declare(strict_types=1);


namespace App\Http\Controllers\User;

use App\Http\Contracts\HttpJson;
use App\Models\User;
use App\UseCases\Users\Listing\UserListUC;
use App\UseCases\Users\Search\UserFindByIdUC;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UsersController extends Controller
{
	public function __construct(
		private readonly UserListUC $userListUC,
		private readonly UserFindByIdUC $userFindByIdUC,
	)
	{
	}

	public function list(Request $request): JsonResponse
	{
		$users = $this->userListUC->run($request->all(), $request->all());
		return HttpJson::OK($users->wrapped('users'));
	}

	public function find(Request $request, int $id): JsonResponse
	{
		$users = $this->userFindByIdUC->run(['id' => $id]);
		return HttpJson::OK($users->wrapped('user'));
	}
}