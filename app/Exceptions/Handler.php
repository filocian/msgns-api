<?php

namespace App\Exceptions;

use App\Http\Contracts\HttpJson;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**w
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        return HttpJson::KO(
            $e->getMessage(),
            $this->resolveExceptionStatus($e)
        );
//        return parent::render($request, $e);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    private function resolveExceptionStatus($e)
    {
        $class = get_class($e);
        return match ($class) {
            AuthenticationException::class => Response::HTTP_UNAUTHORIZED,
            ModelNotFoundException::class => Response::HTTP_NOT_FOUND,
            ValidationException::class => Response::HTTP_BAD_REQUEST,
            HttpException::class => $e->getStatusCode(),
            default => Response::HTTP_INTERNAL_SERVER_ERROR
        };

    }
}
