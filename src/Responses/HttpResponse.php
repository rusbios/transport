<?php
declare(strict_types = 1);

namespace RB\Transport\Responses;

class HttpResponse
{
    protected int $code;
    protected string $type;
    protected string $body;

    public function __construct(int $code, string $type, string $body)
    {
        $this->code = $code;
        $this->type = $type;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}