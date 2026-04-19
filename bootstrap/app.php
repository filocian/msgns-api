<?php

declare(strict_types=1);

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Exceptions\Product\ProductAlreadyRegistered;
use App\Exceptions\Product\ProductNotFoundException;
use App\Exceptions\Product\ProductNotOwnedException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Src\Shared\Core\Errors\DomainException;
use Src\Shared\Infrastructure\Http\DomainExceptionHandler;
use Src\Shared\Infrastructure\Http\ErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response as Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		web: __DIR__ . '/../routes/web.php',
		api: __DIR__ . '/../routes/api.php',
		commands: __DIR__ . '/../routes/console.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware) {
		$middleware->statefulApi();
	})
	->withExceptions(function (Exceptions $exceptions) {
		$exceptions->render(function (DomainException $exception) {
			return app(DomainExceptionHandler::class)->render($exception);
		});

		$exceptions->render(function (TokenMismatchException $exception) {
			return ErrorResponseFactory::error('csrf.mismatch', Response::HTTP_PAGE_EXPIRED);
		});

		$exceptions->render(function (AuthenticationException $exception) {
			return ErrorResponseFactory::error('auth.unauthenticated', Response::HTTP_UNAUTHORIZED);
		});

		$exceptions->render(function (ValidationException $exception) {
			return ErrorResponseFactory::validationFailed($exception->errors(), Response::HTTP_BAD_REQUEST);
		});

		$exceptions->render(function (ModelNotFoundException $exception) {
			return ErrorResponseFactory::error('resource.not_found', Response::HTTP_NOT_FOUND);
		});

		$exceptions->render(function (ProductNotFoundException $exception) {
			return ErrorResponseFactory::error('product.not_found', Response::HTTP_NOT_FOUND);
		});

		$exceptions->render(function (ProductAlreadyRegistered $exception) {
			return ErrorResponseFactory::error('product.already_registered', Response::HTTP_CONFLICT);
		});

		$exceptions->render(function (ProductNotOwnedException $exception) {
			return ErrorResponseFactory::error('product.not_owned', Response::HTTP_FORBIDDEN);
		});

		$exceptions->render(function (ActionNotAllowedException $exception) {
			return ErrorResponseFactory::error('auth.forbidden', Response::HTTP_FORBIDDEN, [
				'action' => $exception->getMessage(),
			]);
		});

		$exceptions->render(function (AuthorizationException $exception) {
			return ErrorResponseFactory::error('auth.forbidden', Response::HTTP_FORBIDDEN);
		});

		$exceptions->render(function (AccessDeniedHttpException $exception) {
			return ErrorResponseFactory::error('auth.forbidden', Response::HTTP_FORBIDDEN);
		});

		$exceptions->render(function (HttpException $exception) {
			$status = $exception->getStatusCode();
			$statusText = Response::$statusTexts[$status] ?? 'unexpected error';

			return ErrorResponseFactory::error(
				'http.' . Str::of($statusText)->snake()->toString(),
				$status
			);
		});

		$exceptions->render(function (\Throwable $exception) {
			Log::error($exception->getMessage(), [
				'exception' => $exception,
			]);

			return ErrorResponseFactory::error('internal.unexpected_error', Response::HTTP_INTERNAL_SERVER_ERROR);
		});
	})->create();
