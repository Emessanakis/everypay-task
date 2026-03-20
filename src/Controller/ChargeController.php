<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Controller;

use Lefteris\EverypayTask\Domain\Exception\NotFoundException;
use Lefteris\EverypayTask\Domain\Exception\PaymentFailedException;
use Lefteris\EverypayTask\Infrastructure\Request;
use Lefteris\EverypayTask\Infrastructure\Response;
use Lefteris\EverypayTask\Service\ChargeRequestDTO;
use Lefteris\EverypayTask\Service\ChargeService;

class ChargeController
{
    public function __construct(
        private readonly ChargeService $chargeService,
    ) {}

    public function handle(Request $request, Response $response): void
    {
        $caller = $request->getAttribute('merchant');

        if ($caller->isAdmin()) {
            $this->json($response, 403, ['error' => 'Forbidden: admin account cannot process payments']);
            return;
        }

        $body = json_decode($request->getBody() ?? '', true);

        if (!is_array($body)) {
            $this->json($response, 400, ['error' => 'Invalid JSON body']);
            return;
        }

        if (empty($body['amount']) || !is_int($body['amount'])) {
            $this->json($response, 422, ['error' => 'Field "amount" is required and must be an integer (cents)']);
            return;
        }

        $dto = new ChargeRequestDTO(
            apiKey:      $request->getHeader('X-API-Key'),
            amount:      $body['amount'],
            currency:    $body['currency'] ?? 'EUR',
            cardNumber:  $body['card_number']  ?? null,
            cvv:         $body['cvv']          ?? null,
            expiryMonth: isset($body['expiry_month']) ? (int) $body['expiry_month'] : null,
            expiryYear:  isset($body['expiry_year'])  ? (int) $body['expiry_year']  : null,
            email:       $body['email']        ?? null,
            password:    $body['password']     ?? null,
        );

        try {
            $result = $this->chargeService->charge($dto);
            $this->json($response, 201, $result->toArray());
        } catch (NotFoundException $e) {
            $this->json($response, 404, ['error' => $e->getMessage()]);
        } catch (PaymentFailedException $e) {
            $this->json($response, 402, ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->json($response, 500, ['error' => 'Internal server error']);
        }
    }

    private function json(Response $response, int $status, array $data): void
    {
        $response
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data))
            ->send();
    }
}
