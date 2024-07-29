<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product\Web;

use App\Exceptions\Product\ProductNotFoundException;
use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\AssignToUserRequest;
use App\Http\Requests\Product\RegisterProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\UseCases\Product\Activation\ActivateUC;
use App\UseCases\Product\Activation\DeactivateUC;
use App\UseCases\Product\Assignment\AssignToCurrentUserUC;
use App\UseCases\Product\Assignment\AssignToUserUC;
use App\UseCases\Product\Filtering\FindByCurrentUserUC;
use App\UseCases\Product\Filtering\FindByIdUC;
use App\UseCases\Product\Listing\ProductListUC;
use App\UseCases\Product\Grouping\RemoveProductLinkUC;
use App\UseCases\Product\Redirect\ProductRedirectionUC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RedirectionController extends Controller
{
	public function __construct(
		private readonly ProductRedirectionUC $ProductRedirectionUC
	)
	{
	}

	public function legacyRedirect(Request $request, string $data): \Illuminate\Http\RedirectResponse
	{
		if($request->input('psw')){
			$parsedUrl = [
				'id' => (int) $data,
				'pass' => $request->input('psw')
			];
		}else{
			$parsedUrl = $this->parseUrlWithQueryParams($data, '&');
		}

		if(!$parsedUrl){
			throw new NotFoundHttpException();
		}

		$productTarget = $this->ProductRedirectionUC->run([
			'id' => $parsedUrl['id'],
			'password' => $parsedUrl['pass']
		]);

		return redirect()->away($productTarget);
	}

	public function v2Redirect(Request $request, int $id, string $password): \Illuminate\Http\RedirectResponse
	{
		$productTarget = $this->ProductRedirectionUC->run([
			'id' => $id,
			'password' => $password
		]);

		return redirect()->away($productTarget);
	}

	private function parseUrlWithQueryParams(string $urlSegment, string $separator): array | null{
		$segments = explode($separator, $urlSegment);

		if(count($segments) > 1){
			$productId = $segments[0];
			$queryValues = explode('=', $segments[1]);
			$pswQueryParam = $queryValues[0] ?? null;
			$pswQueryValue = $queryValues[1] ?? null;

			if($pswQueryParam == 'psw' && $pswQueryValue != null){
				$productPassword = $pswQueryValue;
			}

			return [
				'id' => (int) $productId,
				'pass' => $productPassword ?? null,
			];
		}

		return null;
	}
}
