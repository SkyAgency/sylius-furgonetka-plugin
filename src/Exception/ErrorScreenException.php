<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Exception;

use Exception;

class ErrorScreenException extends Exception
{
    /**
     * Error codes
     */
    public const ERROR_CODE_UNKNOWN = 'unknown';
    public const ERROR_CODE_INACTIVE_ACCOUNT = 'inactive_account';
    public const ERROR_CODE_INVALID_CREDENTIALS = 'invalid_credentials';
    public const ERROR_CODE_MISSING_REQUIRED_PARAMETERS = 'missing_required_parameters';
    public const ERROR_CODE_INTEGRATION_FAILED = 'integration_failed';
    public const ERROR_CODE_UNSUPPORTED_LINK = 'unsupported_link';
    public const ERROR_CODE_EMAIL_EXISTS = 'emailExist';

    public const SUPPORTED_ERROR_CODES = [
        self::ERROR_CODE_UNKNOWN,
        self::ERROR_CODE_INACTIVE_ACCOUNT,
        self::ERROR_CODE_INVALID_CREDENTIALS,
        self::ERROR_CODE_MISSING_REQUIRED_PARAMETERS,
        self::ERROR_CODE_INTEGRATION_FAILED,
        self::ERROR_CODE_UNSUPPORTED_LINK,
        self::ERROR_CODE_EMAIL_EXISTS,
    ];

}
