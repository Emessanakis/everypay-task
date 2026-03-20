<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Service;

use Lefteris\EverypayTask\Domain\Enum\ChargeStatus;
use Lefteris\EverypayTask\Domain\Exception\NotFoundException;
use Lefteris\EverypayTask\Infrastructure\EmailSenderInterface;
use Lefteris\EverypayTask\Repository\ChargeRepositoryInterface;
use Lefteris\EverypayTask\Repository\MerchantRepositoryInterface;

class ReportService
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly ChargeRepositoryInterface   $chargeRepository,
        private readonly EmailSenderInterface        $emailSender,
    ) {}

    /**
     * Collect charges for the given merchant within [from, to] and email a summary.
     *
     * @throws NotFoundException When the merchant ID is unknown.
     */
    public function sendReport(
        string             $merchantId,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): void {
        $merchant = $this->merchantRepository->findById($merchantId);

        if ($merchant === null) {
            throw new NotFoundException("Merchant not found: {$merchantId}");
        }

        $charges = $this->chargeRepository->findByMerchantAndDateRange($merchantId, $from, $to);

        $subject = sprintf(
            'Charge Report for %s (%s – %s)',
            $merchant->name,
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );

        $this->emailSender->send(
            $merchant->email,
            $subject,
            $this->buildBody($merchant->name, $charges, $from, $to),
        );
    }

    private function buildBody(
        string             $merchantName,
        array              $charges,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): string {
        $totalCents = 0;
        $succeeded  = 0;
        $failed     = 0;
        $lines      = [];

        foreach ($charges as $charge) {
            if ($charge->status === ChargeStatus::Succeeded->value) {
                $totalCents += $charge->amount;
                $succeeded++;
            } else {
                $failed++;
            }

            $lines[] = sprintf(
                '[%s] id=%s | %d %s | status=%s | txn=%s',
                $charge->createdAt,
                $charge->id,
                $charge->amount,
                $charge->currency,
                $charge->status,
                $charge->transactionId ?? 'N/A',
            );
        }

        $body  = "Charge Report — {$merchantName}\n";
        $body .= str_repeat('=', 60) . "\n";
        $body .= sprintf("Period : %s to %s\n", $from->format('Y-m-d'), $to->format('Y-m-d'));
        $body .= sprintf(
            "Charges: %d total (succeeded: %d, failed: %d)\n",
            count($charges),
            $succeeded,
            $failed,
        );
        $body .= sprintf(
            "Revenue: %d cents (%.2f %s)\n\n",
            $totalCents,
            $totalCents / 100,
            'EUR',
        );
        $body .= "Details:\n";
        $body .= str_repeat('-', 60) . "\n";
        $body .= ($lines !== [] ? implode("\n", $lines) : 'No charges found for this period.') . "\n";

        return $body;
    }
}
