<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Middleware;

use Lefteris\EverypayTask\Infrastructure\ApiKeyAuthenticator;
use Lefteris\EverypayTask\Infrastructure\Request;
use Lefteris\EverypayTask\Infrastructure\Response;

class AuthMiddleware
{
    public function __construct(
        private readonly ApiKeyAuthenticator $authenticator,
    ) {}

    public function __invoke(Request $request, Response $response, callable $next): void
    {
        $apiKey = $request->getHeader('X-API-Key');

        if ($apiKey === null) {
            $this->unauthorized($response, 'Missing X-API-Key header');
            return;
        }

        $merchant = $this->authenticator->authenticate($apiKey);

        if ($merchant === null) {
            $this->unauthorized($response, 'Invalid API key');
            return;
        }

        $request->setAttribute('merchant', $merchant);

        $next($request, $response);
    }

    private function unauthorized(Response $response, string $message): void
    {
        $response
            ->setStatusCode(401)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode(['error' => $message]))
            ->send();
    }
}
