<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {

            abort(401, 'Authentication Failed: Invalid or expired token.');

        } elseif ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {

            abort(404, 'The requested resource was not found.');

        } elseif ($exception instanceof \Illuminate\Database\QueryException) {

            abort(500, 'QueryException: Something went wrong!');

        } elseif ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {

            $headers = $exception->getHeaders();

            switch ($exception->getStatusCode()) {
                case 401:
                    $default_message = 'Authentication Failed: Invalid or expired token.';
                    break;

                case 403:
                    $default_message = 'Insufficient privileges to perform this action.';
                    break;

                case 404:
                    $default_message = 'The requested resource was not found.';
                    break;

                case 405:
                    $default_message = 'The page you requested was not found, use another endpoint instead.';
                    break;

                default:
                    $default_message = 'An error was encountered.';
                    break;
            }

            return json_response()->error($exception->getMessage() ?: $default_message, $exception->getStatusCode());

        } elseif ($exception instanceof \Illuminate\Validation\ValidationException) {

            return json_response()->error($exception->validator->messages() ?: 'Validation error.', 422);

        } elseif ($exception instanceof \RuntimeException
            || $exception instanceof \BadMethodCallException
            || $exception instanceof \ReflectionException) {

            abort(500, $exception->getMessage());

        } elseif ($exception instanceof \Exception) {

            abort(500, $exception->getMessage());

        }

        return parent::render($request, $exception);
    }
}
