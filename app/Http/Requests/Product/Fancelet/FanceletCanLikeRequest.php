<?php

declare(strict_types=1);

namespace App\Http\Requests\Product\Fancelet;

use App\Infrastructure\Services\Product\Fancelet\FanceletService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class FanceletCanLikeRequest extends FormRequest
{
	public function authorize(Request $request, FanceletService $fanceletService): bool
	{
		$productId = (int) $request->route('id');
		$contentId = (int) $request->route('contentId');
		$contentType = $request->route('contentType');

		$canLike = $fanceletService->canLike($productId, $contentId, $contentType);

		return !(!$canLike)
			 
		

		;
	}

	public function rules(): array
	{
		return [];
	}
}
