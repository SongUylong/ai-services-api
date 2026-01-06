<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class ApiException extends Exception
{
    protected int $statusCode;
    protected string $errorCode;
    protected array $errorDetails;

    public function __construct(
        string $message = 'An error occurred',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        string $errorCode = 'BAD_REQUEST',
        array $errorDetails = []
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->errorDetails = $errorDetails;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    public function render($request)
    {
        $error = [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
        ];

        if (!empty($this->errorDetails)) {
            $error['details'] = $this->errorDetails;
        }

        $response = [
            'success' => false,
            'error' => $error,
        ];

        // Add debug information in debug mode
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($this),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => collect($this->getTrace())->take(5)->map(function ($trace) {
                    return [
                        'file' => $trace['file'] ?? 'unknown',
                        'line' => $trace['line'] ?? 0,
                        'function' => $trace['function'] ?? 'unknown',
                    ];
                })->toArray(),
            ];
        }

        return response()->json($response, $this->statusCode);
    }
}
