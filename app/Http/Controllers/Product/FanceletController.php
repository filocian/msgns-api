<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\Fancelet\FanceletCanLikeRequest;
use App\Infrastructure\Services\Product\Fancelet\FanceletService;
use App\UseCases\Product\Fancelet\Actions\LoveFanceletActionUC;
use App\UseCases\Product\Fancelet\Comments\FanceletCommentUC;
use App\UseCases\Product\Fancelet\Likes\FanceletContentLikeUC;
use App\UseCases\Product\Fancelet\LogicByType\LoveUC;
use Exception;
use Illuminate\Http\Request;

final class FanceletController extends Controller
{
	public function __construct(
		private readonly LoveUC $loveUC,
		private readonly LoveFanceletActionUC $loveActionUC,
		private readonly FanceletContentLikeUC $contentLikeUC,
		private readonly FanceletCommentUC $fanceletCommentUC,
		private readonly FanceletService $fanceletService
	) {}

	public function getLoveContent(int $productId, string $password)
	{
		$content = $this->loveUC->run([
			'product_id' => $productId,
			'password' => $password,
		]);

		return HttpJson::OK($content->wrapped('content'));
	}

	public function loveAction(Request $request)
	{
		$productId = $request->get('product_id');
		$productPass = $request->get('product_password');
		$message = $request->get('message');

		try {
			$this->loveActionUC->run([
				'product_id' => $productId,
				'product_password' => $productPass,
				'message' => $message,
			]);
		} catch (Exception $exception) {
			return HttpJson::KO('unable_to_send_fancelet_love_action_message');
		}

		return HttpJson::OK('fancelet_love_action_message_sent');
	}

	public function sendComment(Request $request)
	{
		$productId = $request->get('product_id');
		$productPass = $request->get('product_password');
		$message = $request->get('message');

		try {
			$this->fanceletCommentUC->run([
				'product_id' => $productId,
				'product_password' => $productPass,
				'message' => $message,
			]);
		} catch (Exception $exception) {
			return HttpJson::KO('unable_to_send_fancelet_comment');
		}

		return HttpJson::OK('fancelet_message_sent');
	}

	public function addContentLike(
		FanceletCanLikeRequest $request,
		int $productId,
		string $productPass,
		string $contentType,
		int $contentId
	) {
		$content = $this->contentLikeUC->run([
			'product_id' => $productId,
			'content_id' => $contentId,
			'content_type' => $contentType,
		]);

		return HttpJson::OK(['likes' => $content]);
	}

	public function canLike(int $productId, string $contentType, int $contentId)
	{
		$canLike = $this->fanceletService->canLike($productId, $contentId, $contentType);
		return HttpJson::OK(['can_like' => $canLike]);
	}
}
