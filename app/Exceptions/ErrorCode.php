<?php

namespace App\Exceptions;

class ErrorCode
{
    // Authentication & Authorization
    public const UNAUTHENTICATED = 'UNAUTHENTICATED';
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const FORBIDDEN = 'FORBIDDEN';
    public const INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    public const AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';
    public const TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    public const TOKEN_INVALID = 'TOKEN_INVALID';
    public const TOKEN_REVOKED = 'TOKEN_REVOKED';
    public const INVALID_REFRESH_TOKEN = 'INVALID_REFRESH_TOKEN';
    public const SESSION_EXPIRED = 'SESSION_EXPIRED';

    // Resource Errors
    public const NOT_FOUND = 'NOT_FOUND';
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const ROUTE_NOT_FOUND = 'ROUTE_NOT_FOUND';

    // Validation Errors
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const INVALID_INPUT = 'INVALID_INPUT';

    // Conflict Errors
    public const ALREADY_EXISTS = 'ALREADY_EXISTS';
    public const DUPLICATE_ENTRY = 'DUPLICATE_ENTRY';
    public const CONFLICT = 'CONFLICT';

    // Server Errors
    public const INTERNAL_ERROR = 'INTERNAL_ERROR';
    public const DATABASE_ERROR = 'DATABASE_ERROR';
    public const SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';
    public const MAINTENANCE_MODE = 'MAINTENANCE_MODE';

    // Rate Limiting
    public const TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';

    // Request Errors
    public const BAD_REQUEST = 'BAD_REQUEST';
    public const METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    public const INVALID_REQUEST = 'INVALID_REQUEST';
    public const PAYLOAD_TOO_LARGE = 'PAYLOAD_TOO_LARGE';
    public const UNSUPPORTED_MEDIA_TYPE = 'UNSUPPORTED_MEDIA_TYPE';

    // File/Upload Errors
    public const FILE_TOO_LARGE = 'FILE_TOO_LARGE';
    public const FILE_UPLOAD_ERROR = 'FILE_UPLOAD_ERROR';
    public const INVALID_FILE_TYPE = 'INVALID_FILE_TYPE';

    // Business Logic Errors
    public const OPERATION_FAILED = 'OPERATION_FAILED';
    public const INSUFFICIENT_PERMISSIONS = 'INSUFFICIENT_PERMISSIONS';
}
