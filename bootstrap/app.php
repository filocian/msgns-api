<?php

declare(strict_types=1);

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Exceptions\Product\ProductAlreadyRegistered;
use App\Exceptions\Product\ProductNotFoundException;
use App\Exceptions\Product\ProductNotOwnedException;
use App\Http\Contracts\HttpJson;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Src\Shared\Core\Errors\DomainException;
use Src\Shared\Infrastructure\Http\DomainExceptionHandler;
use Symfony\Component\HttpFoundation\Response as Response;
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

		$exceptions->render(function (Exception $exception) {
			if ($exception instanceof ValidationException) {
				return HttpJson::KO('validation_error', Response::HTTP_BAD_REQUEST, [
					'code' => 'validation_error',
					'context' => [
						'errors' => $exception->errors(),
					],
				]);
			}

			$class = get_class($exception);
			$status = match ($class) {
				ProductNotOwnedException::class,
				ProductAlreadyRegistered::class,
				AuthenticationException::class,
				ActionNotAllowedException::class => Response::HTTP_UNAUTHORIZED,
				ModelNotFoundException::class,
				ProductNotFoundException::class => Response::HTTP_NOT_FOUND,
				default => Response::HTTP_INTERNAL_SERVER_ERROR
			};

			if ($exception instanceof HttpException) {
				$status = $exception->getStatusCode();
			}

			return HttpJson::KO(
				$exception->getMessage(),
				$status
			);
		});
	})->create();
