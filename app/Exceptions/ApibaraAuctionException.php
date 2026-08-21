<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApibaraAuctionException extends Exception
{
    public function __construct(
        string $message,
        protected int $status = 502,
        protected ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return array{message: string, code: ?string}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
        ];
    }
}
