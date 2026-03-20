<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Infrastructure;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private ?string $body = null;

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        if ($this->body !== null) {
            echo $this->body;
        }
    }
}