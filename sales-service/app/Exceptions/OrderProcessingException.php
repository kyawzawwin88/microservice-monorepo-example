<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for order processing failures.
 * When thrown, the order state transitions to 'failed'
 * and the error message is stored in state_failure_description.
 */
class OrderProcessingException extends Exception
{
    private string $correlationId;

    public function __construct(
        string $message,
        string $correlationId,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->correlationId = $correlationId;
        parent::__construct($message, $code, $previous);
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function context(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'error' => $this->getMessage(),
        ];
    }
}
