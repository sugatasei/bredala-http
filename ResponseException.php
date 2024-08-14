<?php

namespace Bredala\Http;

use Exception;
use JsonSerializable;
use Throwable;

class ResponseException extends Exception implements JsonSerializable
{
    private array $errors = [];
    private array $extra = [];

    // -------------------------------------------------------------------------

    public function __construct(int $status, string $message = 'default', ?Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }

    // -------------------------------------------------------------------------
    // Additionnal data
    // -------------------------------------------------------------------------

    /**
     * Set error details
     *
     * @param array $errors
     * @return static
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Add error detail
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addError(string $key, mixed $value): static
    {
        $this->errors[$key] = $value;
        return $this;
    }

    /**
     * Set extra data
     *
     * @param array $extra
     * @return static
     */
    public function setExtra(array $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * Add extra value
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addExtra(string $key, mixed $value): static
    {
        $this->extra[$key] = $value;
        return $this;
    }

    // -------------------------------------------------------------------------

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'status' => $this->getCode(),
            'error' => $this->getMessage(),
            'errors' => $this->getErrors(),
            'extra' => $this->getExtra(),
        ];
    }

    // -------------------------------------------------------------------------
}
