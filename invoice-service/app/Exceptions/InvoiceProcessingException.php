<?php

namespace App\Exceptions;

use Exception;

class InvoiceProcessingException extends Exception
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
