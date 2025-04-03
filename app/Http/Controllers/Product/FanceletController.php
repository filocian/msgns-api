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
use App\UseCases\Product\Fancelet\LogicByType\BibleUC;
use App\UseCases\Product\Fancelet\LogicByType\LoveUC;
use App\UseCases\Product\Fancelet\Pairing\AnonymousFanceletPairingUC;
use App\UseCases\Product\Grouping\SetFanceletGroupUC;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FanceletController extends Controller
{
	public function __construct(
		private readonly LoveUC $loveUC,
		private readonly BibleUC $bibleUC,
		private readonly LoveFanceletActionUC $loveActionUC,
		private readonly FanceletContentLikeUC $contentLikeUC,
		private readonly FanceletCommentUC $fanceletCommentUC,
		private readonly FanceletService $fanceletService,
		private readonly SetFanceletGroupUC $setFanceletGroupUC,
		private readonly AnonymousFanceletPairingUC $anonymousFanceletPairingUC,
	) {}

	public function getLoveContent(int $productId, string $password): JsonResponse
	{
		$content = $this->loveUC->run([
			'product_id' => $productId,
			'password' => $password,
		]);

		return HttpJson::OK($content->wrapped('content'));
	}

	public function getBibleContent(int $productId, string $password): JsonResponse
	{
		$content = $this->bibleUC->run([
			'product_id' => $productId,
			'password' => $password,
		]);

		return HttpJson::OK($content->wrapped('content'));
	}

	public function loveAction(Request $request): JsonResponse
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

	public function bibleAction(Request $request): JsonResponse
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

	public function sendComment(Request $request): JsonResponse
	{
		$productId = $request->get('product_id');
		$productGroup = $request->get('product_group');
		$message = $request->get('message');

		try {
			$this->fanceletCommentUC->run([
				'product_id' => $productId,
				'product_group' => $productGroup,
				'message' => $message,
			]);
		} catch (Exception $exception) {
			return HttpJson::KO('unable_to_send_fancelet_comment');
		}

		return HttpJson::OK('fancelet_message_sent');
	}

	public function groupFancelets(Request $request): JsonResponse
	{
		$productId = $request->get('parent_product_id');
		$productPasswordId = $request->get('parent_product_password');
		$childrenProductId = $request->get('children_product_ids');

		try {
			$this->setFanceletGroupUC->run([
				'parent_product_id' => (int) $productId,
				'parent_product_password' => (string) $productPasswordId,
				'children_product_ids' => $childrenProductId,
			]);
		} catch (Exception $exception) {
			return HttpJson::KO('unable_to_group_fancelets', 500, [$exception->getMessage()]);
		}

		return HttpJson::OK('fancelet_grouped');
	}

	public function addContentLike(
		FanceletCanLikeRequest $request,
		int $productId,
		string $productPass,
		string $contentType,
		int $contentId
	): JsonResponse {
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

	public function getGroupComments(string $group_id): JsonResponse
	{
		$comments = $this->fanceletService->getGroupComments($group_id);

		return HttpJson::OK(['comments' => $comments]);
	}

	public function anonymousFanceletPairing(Request $request): JsonResponse	{
		$masterList = $request->input('master_list');
		$slaveList = $request->input('slave_list');

		$pairing = $this->anonymousFanceletPairingUC->run([
			'master_list' => $masterList,
			'slave_list' =>	$slaveList
		]);

		return HttpJson::OK(['pairing' => $pairing]);
	}
}
