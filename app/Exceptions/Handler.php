<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;

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
        if($exception instanceof InvalidSignatureException) {
            return response()->json([
                'data' => [
                    'message' => 'Your signing request is invalid, please try again.',
                    'error' => $exception->getMessage()
                ]
            ], 403);
        }

        if($exception instanceof AuthenticationException ){

            return response()->json([
                'data' => [
                    'message' => 'You are not currently authenticated.',
                    'error' => $exception->getMessage()
                ]
            ], 401);

        }

        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'data' => [
                    'message' => 'You do not have sufficient privileges to carry out this action.',
                    'error' => $exception->getMessage()
                ]
            ], 403);
        }

        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'data' => [
                    'message' => 'Resource not found for provided ID.',
                    'error' => $exception->getMessage()
                ]
            ], 404);
        }


        if ($exception instanceof ValidationException || $exception instanceof ApiException) {
            return response()->json([
                'data' => [
                    'message' => $exception->getMessage(),
                    'errors' => isset($exception->validator) ? $exception->validator->getMessageBag() : []
                ]
            ], 422);
        }

         Log::info($exception);
         return parent::render($request, $exception);
    }
}
