<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Controller;

use Lefteris\EverypayTask\Domain\Exception\NotFoundException;
use Lefteris\EverypayTask\Infrastructure\Request;
use Lefteris\EverypayTask\Infrastructure\Response;
use Lefteris\EverypayTask\Repository\ChargeRepositoryInterface;
use Lefteris\EverypayTask\Repository\MerchantRepositoryInterface;

class MerchantChargesController
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly ChargeRepositoryInterface $chargeRepository,
    ) {}

    public function handle(Request $request, Response $response, array $vars): void
    {
        $merchantId = $vars['merchantId'] ?? '';

        $merchant = $this->merchantRepository->findById($merchantId);

        if ($merchant === null) {
            $this->json($response, 404, ['error' => 'Merchant not found']);
            return;
        }

        $charges = $this->chargeRepository->findByMerchant($merchantId);

        $this->json($response, 200, array_map(
            static fn($charge) => [
                'id'             => $charge->id,
                'merchant_id'    => $charge->merchantId,
                'amount'         => $charge->amount,
                'currency'       => $charge->currency,
                'status'         => $charge->status,
                'transaction_id' => $charge->transactionId,
                'created_at'     => $charge->createdAt,
            ],
            $charges
        ));
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
