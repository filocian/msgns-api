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
		$exceptions->render(function (Exception $exception) {
			$class = get_class($exception);
			$status = match ($class) {
				ProductNotOwnedException::class,
				ProductAlreadyRegistered::class,
				AuthenticationException::class,
				ActionNotAllowedException::class => Response::HTTP_UNAUTHORIZED,
				ModelNotFoundException::class,
				ProductNotFoundException::class => Response::HTTP_NOT_FOUND,
				ValidationException::class => Response::HTTP_BAD_REQUEST,
				HttpException::class => $exception->getStatusCode(),

				default => Response::HTTP_INTERNAL_SERVER_ERROR
			};

			return HttpJson::KO(
				$exception->getMessage(),
				$status
			);
		});
	})->create();
