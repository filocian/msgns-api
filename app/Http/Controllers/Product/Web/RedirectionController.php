<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product\Web;

use App\Http\Contracts\Controller;
use App\Models\Product;
use App\UseCases\Product\Redirect\ProductRedirectionUC;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RedirectionController extends Controller
{
	public function __construct(
		private readonly ProductRedirectionUC $ProductRedirectionUC
	) {}

	public function legacyRedirect(Request $request, string $data)
	{
		$browserLocales = $request->header('Accept-language');
		$browserLocales = filter_var($browserLocales, FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Sanitiza la cadena

		if ($request->input('psw')) {
			$parsedUrl = [
				'id' => $data,
				'pass' => $request->input('psw') ?? '',
			];
		} else {
			$parsedUrl = $this->parseUrlWithQueryParams($data);
		}

		if (!$parsedUrl) {
			throw new NotFoundHttpException();
		}

		$productTarget = $this->ProductRedirectionUC->run([
			'id' => $parsedUrl['id'],
			'password' => $parsedUrl['pass'],
			'browserLocales' => $browserLocales,
		]);

		if (is_string($productTarget)) {
			return redirect()->away($productTarget);
		}

		return $productTarget;
	}

	public function v2Redirect(Request $request, int $id, string $password)
	{
		$browserLocales = $request->header('Accept-language');
		$browserLocales = filter_var($browserLocales, FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Sanitiza la cadena

		$productTarget = $this->ProductRedirectionUC->run([
			'id' => $id,
			'password' => $password,
			'browserLocales' => $browserLocales,
		]);

		if (is_string($productTarget)) {
			return redirect()->away($productTarget);
		}

		return $productTarget;
	}

	private function parseUrlWithQueryParams(string $urlSegment): array|null
	{
		$segments = explode('&', $urlSegment);

		if (count($segments) > 1) {
			$productId = $segments[0];
			$queryValues = explode('=', $segments[1]);
			$pswQueryParam = $queryValues[0] ?? null;
			$pswQueryValue = $queryValues[1] ?? '';

			if ($pswQueryParam === 'psw' && $pswQueryValue !== null) {
				$productPassword = $pswQueryValue;
			}

			return [
				'id' => (int) $productId,
				'pass' => $productPassword ?? '',
			];
		}

		return null;
	}
}
