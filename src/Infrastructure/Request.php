<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Infrastructure;

class Request
{
    private array $query;
    private array $post;
    private array $server;
    private array $headers;
    private ?string $body;
    private array $attributes = [];

    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->headers = getallheaders() ?: [];
        $this->body = file_get_contents('php://input');
    }

    public function getQueryParams(): array
    {
        return $this->query;
    }

    public function getPostParams(): array
    {
        return $this->post;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function getUri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $queryPos = strpos($uri, '?');
        if ($queryPos !== false) {
            $uri = substr($uri, 0, $queryPos);
        }
        return $uri;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}