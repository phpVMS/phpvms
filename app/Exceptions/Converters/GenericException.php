<?php

namespace App\Exceptions\Converters;

use App\Exceptions\HttpException;
use Exception;

class GenericException extends HttpException
{
    private $exception;

    public function __construct(Exception $exception)
    {
        $this->exception = $exception;
        parent::__construct(
            503,
            $exception->getMessage()
        );
    }

    /**
     * Return the RFC 7807 error type (without the URL root)
     */
    public function getErrorType(): string
    {
        return 'internal-error';
    }

    /**
     * Get the detailed error string
     */
    public function getErrorDetails(): string
    {
        return $this->getMessage();
    }

    /**
     * Return an array with the error details, merged with the RFC7807 response
     */
    public function getErrorMetadata(): array
    {
        // Only add trace if in dev
        if (config('app.env') === 'dev') {
            return [
                'trace' => $this->exception->getTrace()[0],
            ];
        }

        return [];
    }
}
