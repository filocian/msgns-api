<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\Fancelet\FanceletAnonymousPairingRequest;
use App\Http\Requests\Product\Fancelet\FanceletCanLikeRequest;
use App\Http\Requests\Product\Fancelet\FanceletRegisteredUserRequest;
use App\Infrastructure\Services\Product\Fancelet\FanceletService;
use App\UseCases\Product\Fancelet\Actions\InnerPeaceFanceletActionUC;
use App\UseCases\Product\Fancelet\Actions\LoveFanceletActionUC;
use App\UseCases\Product\Fancelet\Comments\FanceletCommentUC;
use App\UseCases\Product\Fancelet\Likes\FanceletContentLikeUC;
use App\UseCases\Product\Fancelet\LogicByType\BibleUC;
use App\UseCases\Product\Fancelet\LogicByType\InnerPeaceUC;
use App\UseCases\Product\Fancelet\LogicByType\LoveUC;
use App\UseCases\Product\Fancelet\Pairing\AnonymousFanceletPairingUC;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FanceletController extends Controller
{
	public function __construct(
		private readonly LoveUC $loveUC,
		private readonly BibleUC $bibleUC,
		private readonly InnerPeaceUC $innerPeaceUC,
		private readonly LoveFanceletActionUC $loveActionUC,
		private readonly InnerPeaceFanceletActionUC $innerPeaceFanceletActionUC,
		private readonly FanceletContentLikeUC $contentLikeUC,
		private readonly FanceletCommentUC $fanceletCommentUC,
		private readonly FanceletService $fanceletService,
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

	public function getYogaContent(int $productId, string $password): JsonResponse
	{
		$content = $this->bibleUC->run([
			'product_id' => $productId,
			'password' => $password,
		]);

		return HttpJson::OK($content->wrapped('content'));
	}

	public function getInnerPeaceContent(
		FanceletRegisteredUserRequest $request,
		int $productId,
		string $password
	): JsonResponse {
		$content = $this->innerPeaceUC->run([
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

	public function innerPeaceAction(Request $request): JsonResponse
	{
		$productId = $request->get('product_id');
		$productPass = $request->get('product_password');
		$message = $request->get('message');

		try {
			$this->innerPeaceFanceletActionUC->run([
				'product_id' => $productId,
				'product_password' => $productPass,
				'message' => $message,
			]);
		} catch (Exception $exception) {
			return HttpJson::KO('unable_to_send_fancelet_IP_action_mail');
		}

		return HttpJson::OK('fancelet_IP_action_mail_sent');
	}

	public function sendComment(Request $request): JsonResponse
	{
		$productId = $request->get('product_id');
		$productGroup = $request->get('product_group');
		$message = $request->get('message');
		$tags = $request->get('tags');

		try {
			$this->fanceletCommentUC->run([
				'product_id' => $productId,
				'product_group' => $productGroup,
				'message' => $message,
				'tags' => $tags,
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

	public function getGroupComments(Request $request, string $group_id): JsonResponse
	{
		$includeTags = $request->get('include_tags');
		$filterTags = $request->get('filter_tags');
		$comments = $this->fanceletService->getGroupComments($group_id, $includeTags, $filterTags);

		return HttpJson::OK(['comments' => $comments]);
	}

	public function anonymousFanceletPairing(FanceletAnonymousPairingRequest $request): JsonResponse
	{
		$pairs = $request->input('pairs');
		$productTypeId = $request->input('product_type_id');

		$pairing = $this->anonymousFanceletPairingUC->run([
			'pairs' => $pairs,
			'product_type_id' => (int) $productTypeId,
		]);

		if ($pairing === null) {
			return HttpJson::KO('invalid_product_type', 500, ['product_type_id' => $productTypeId]);
		}

		return HttpJson::OK(['pairing_result' => $pairing]);
	}

	public function setVideoViewed(FanceletRegisteredUserRequest $request, int $video_id): JsonResponse
	{
		$productId = $request->input('product_id');
		$productPwd = $request->input('product_password');
		$availableVideos = $this->fanceletService->markVideoViewed($productId, $video_id, $productPwd);


		return HttpJson::OK($availableVideos->wrapped('content'));
	}
}
