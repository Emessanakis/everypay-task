<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Controller;

use Lefteris\EverypayTask\Infrastructure\Request;
use Lefteris\EverypayTask\Infrastructure\Response;
use Lefteris\EverypayTask\Repository\ChargeRepositoryInterface;
use Lefteris\EverypayTask\Repository\MerchantRepositoryInterface;

class ChargesController
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly ChargeRepositoryInterface $chargeRepository,
    ) {}

    public function handle(Request $request, Response $response, array $vars): void
    {
        $caller = $request->getAttribute('merchant');

        if (!$caller->isAdmin()) {
            $this->json($response, 403, ['error' => 'Forbidden: admin access required']);
            return;
        }

        $merchantId = $vars['merchantId'] ?? null;
        $date       = $vars['date']       ?? null;
        $range      = $vars['range']      ?? null;

        if ($merchantId !== null) {
            $merchant = $this->merchantRepository->findById($merchantId);
            if ($merchant === null) {
                $this->json($response, 404, ['error' => 'Merchant not found']);
                return;
            }
        }

        if ($range !== null) {
            [$fromDate, $toDate] = explode(',', $range, 2);
            $from = new \DateTime($fromDate . ' 00:00:00');
            $to   = new \DateTime($toDate   . ' 23:59:59');

            $charges = $merchantId !== null
                ? $this->chargeRepository->findByMerchantAndDateRange($merchantId, $from, $to)
                : $this->chargeRepository->findByDateRange($from, $to);
        } elseif ($date !== null) {
            $from = new \DateTime($date . ' 00:00:00');
            $to   = new \DateTime($date . ' 23:59:59');

            $charges = $merchantId !== null
                ? $this->chargeRepository->findByMerchantAndDateRange($merchantId, $from, $to)
                : $this->chargeRepository->findByDateRange($from, $to);
        } elseif ($merchantId !== null) {
            $charges = $this->chargeRepository->findByMerchant($merchantId);
        } else {
            $charges = $this->chargeRepository->findAll();
        }

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
