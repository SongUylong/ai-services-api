<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Exceptions\MissingScopeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        ValidationException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
        ThrottleRequestsException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if ($this->shouldLogError($e)) {
                $context = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'ip' => request()->ip(),
                ];

                // Add user context if authenticated
                if (auth()->check()) {
                    $context['user_id'] = auth()->id();
                    $context['user_email'] = auth()->user()->email ?? null;
                }

                // Add trace for 500 errors
                if ($this->getStatusCode($e) >= 500) {
                    $context['trace'] = $e->getTraceAsString();
                }

                Log::error('Application Error', $context);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderJsonException($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function prepareJsonResponse($request, Throwable $e)
    {
        return $this->renderJsonException($request, $e);
    }

    public function renderJsonException($request, Throwable $e)
    {
        if ($e instanceof ApiException) {
            return $e->render($request);
        }

        $statusCode = $this->getStatusCode($e);
        $errorCode = $this->getErrorCode($e);
        $message = $this->getErrorMessage($e);

        $error = [
            'code' => $errorCode,
            'message' => $message,
        ];

        // Add validation errors
        if ($e instanceof ValidationException) {
            $error['details'] = ['fields' => $e->errors()];
        }

        // Add throttle details
        if ($e instanceof ThrottleRequestsException || $e instanceof TooManyRequestsHttpException) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? null;
            if ($retryAfter) {
                $error['details'] = [
                    'retry_after' => (int) $retryAfter,
                    'retry_after_message' => "Please try again in {$retryAfter} seconds",
                ];
            }
        }

        $response = [
            'success' => false,
            'error' => $error,
        ];

        // Add debug information in debug mode
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->map(function ($trace) {
                    return [
                        'file' => $trace['file'] ?? 'unknown',
                        'line' => $trace['line'] ?? 0,
                        'function' => $trace['function'] ?? 'unknown',
                    ];
                })->toArray(),
            ];
        }

        // Build response with appropriate headers
        $jsonResponse = response()->json($response, $statusCode);

        // Add rate limit headers if available
        if ($e instanceof ThrottleRequestsException || $e instanceof TooManyRequestsHttpException) {
            foreach ($e->getHeaders() as $key => $value) {
                $jsonResponse->header($key, $value);
            }
        }

        return $jsonResponse;
    }

    protected function getStatusCode(Throwable $e): int
    {
        // Custom API exceptions
        if ($e instanceof ApiException) {
            return $e->getStatusCode();
        }

        // Authentication & Authorization
        if ($e instanceof AuthenticationException || $e instanceof UnauthorizedHttpException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($e instanceof AuthorizationException || 
            $e instanceof AccessDeniedHttpException || 
            $e instanceof MissingScopeException) {
            return Response::HTTP_FORBIDDEN;
        }

        // Resource Not Found
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return Response::HTTP_NOT_FOUND;
        }

        // Validation
        if ($e instanceof ValidationException) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        // Rate Limiting
        if ($e instanceof ThrottleRequestsException || $e instanceof TooManyRequestsHttpException) {
            return Response::HTTP_TOO_MANY_REQUESTS;
        }

        // Bad Requests
        if ($e instanceof BadRequestHttpException) {
            return Response::HTTP_BAD_REQUEST;
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return Response::HTTP_METHOD_NOT_ALLOWED;
        }

        if ($e instanceof UnsupportedMediaTypeHttpException) {
            return Response::HTTP_UNSUPPORTED_MEDIA_TYPE;
        }

        // Payload Too Large
        if ($e instanceof PostTooLargeException) {
            return Response::HTTP_REQUEST_ENTITY_TOO_LARGE;
        }

        // Service Unavailable
        if ($e instanceof ServiceUnavailableHttpException) {
            return Response::HTTP_SERVICE_UNAVAILABLE;
        }

        // Database Errors
        if ($e instanceof QueryException) {
            return Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        // Generic status code getter
        if (method_exists($e, 'getStatusCode')) {
            $statusCode = call_user_func([$e, 'getStatusCode']);
            if (is_int($statusCode) && $statusCode >= 100 && $statusCode < 600) {
                return $statusCode;
            }
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    protected function getErrorCode(Throwable $e): string
    {
        // Custom API exceptions
        if ($e instanceof ApiException) {
            return $e->getErrorCode();
        }

        // Authentication & Authorization
        if ($e instanceof AuthenticationException || $e instanceof UnauthorizedHttpException) {
            return ErrorCode::UNAUTHENTICATED;
        }

        if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
            return ErrorCode::UNAUTHORIZED;
        }

        if ($e instanceof MissingScopeException) {
            return ErrorCode::INSUFFICIENT_PERMISSIONS;
        }

        // Resource Errors
        if ($e instanceof ModelNotFoundException) {
            return ErrorCode::RESOURCE_NOT_FOUND;
        }

        if ($e instanceof NotFoundHttpException) {
            // Check if it's a route not found
            $message = $e->getMessage();
            if (str_contains($message, 'No query results')) {
                return ErrorCode::RESOURCE_NOT_FOUND;
            }
            return ErrorCode::ROUTE_NOT_FOUND;
        }

        // Validation Errors
        if ($e instanceof ValidationException) {
            return ErrorCode::VALIDATION_ERROR;
        }

        // Rate Limiting
        if ($e instanceof ThrottleRequestsException || $e instanceof TooManyRequestsHttpException) {
            return ErrorCode::TOO_MANY_REQUESTS;
        }

        // Request Errors
        if ($e instanceof BadRequestHttpException) {
            return ErrorCode::BAD_REQUEST;
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return ErrorCode::METHOD_NOT_ALLOWED;
        }

        if ($e instanceof UnsupportedMediaTypeHttpException) {
            return ErrorCode::UNSUPPORTED_MEDIA_TYPE;
        }

        if ($e instanceof PostTooLargeException) {
            return ErrorCode::PAYLOAD_TOO_LARGE;
        }

        // Server Errors
        if ($e instanceof ServiceUnavailableHttpException) {
            return ErrorCode::SERVICE_UNAVAILABLE;
        }

        if ($e instanceof QueryException) {
            return ErrorCode::DATABASE_ERROR;
        }

        return ErrorCode::INTERNAL_ERROR;
    }

    protected function getErrorMessage(Throwable $e): string
    {
        // Authentication & Authorization
        if ($e instanceof AuthenticationException || $e instanceof UnauthorizedHttpException) {
            return $e->getMessage() ?: 'Authentication required';
        }

        if ($e instanceof AuthorizationException) {
            return $e->getMessage() ?: 'You are not authorized to perform this action';
        }

        if ($e instanceof AccessDeniedHttpException) {
            return $e->getMessage() ?: 'Access denied';
        }

        if ($e instanceof MissingScopeException) {
            return 'Insufficient permissions to access this resource';
        }

        // Resource Errors
        if ($e instanceof ModelNotFoundException) {
            $model = $e->getModel();
            $modelName = class_basename($model);
            return "The requested {$modelName} was not found";
        }

        if ($e instanceof NotFoundHttpException) {
            $message = $e->getMessage();
            if (str_contains($message, 'No query results')) {
                return 'The requested resource was not found';
            }
            return $e->getMessage() ?: 'The requested endpoint was not found';
        }

        // Validation Errors
        if ($e instanceof ValidationException) {
            $errors = $e->errors();
            if (count($errors) === 1) {
                $firstError = reset($errors);
                return is_array($firstError) ? $firstError[0] : $firstError;
            }
            return 'The given data was invalid';
        }

        // Rate Limiting
        if ($e instanceof ThrottleRequestsException || $e instanceof TooManyRequestsHttpException) {
            return 'Too many requests. Please slow down';
        }

        // Request Errors
        if ($e instanceof BadRequestHttpException) {
            return $e->getMessage() ?: 'Bad request';
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return 'The specified HTTP method is not allowed for this endpoint';
        }

        if ($e instanceof UnsupportedMediaTypeHttpException) {
            return 'Unsupported media type';
        }

        if ($e instanceof PostTooLargeException) {
            return 'The request payload is too large';
        }

        // Server Errors
        if ($e instanceof ServiceUnavailableHttpException) {
            return 'Service temporarily unavailable. Please try again later';
        }

        if ($e instanceof QueryException) {
            // Sanitize database errors in production
            if (config('app.debug')) {
                return $e->getMessage();
            }
            
            // Check for common database errors
            $errorCode = $e->getCode();
            if ($errorCode === '23000') {
                return 'A database constraint violation occurred';
            }
            
            return 'A database error occurred';
        }

        // Generic errors
        if (config('app.debug')) {
            return $e->getMessage() ?: 'An unexpected error occurred';
        }

        // Don't leak internal errors in production
        return 'An unexpected error occurred. Please try again later';
    }

    protected function shouldLogError(Throwable $e): bool
    {
        // Don't log client errors (4xx) that are expected
        if ($e instanceof ValidationException ||
            $e instanceof AuthenticationException ||
            $e instanceof AuthorizationException ||
            $e instanceof ModelNotFoundException ||
            $e instanceof NotFoundHttpException ||
            $e instanceof ThrottleRequestsException ||
            $e instanceof TooManyRequestsHttpException ||
            $e instanceof BadRequestHttpException ||
            $e instanceof MethodNotAllowedHttpException) {
            return false;
        }

        // Log all server errors (5xx) and database errors
        $statusCode = $this->getStatusCode($e);
        return $statusCode >= 500 || $e instanceof QueryException;
    }
}
