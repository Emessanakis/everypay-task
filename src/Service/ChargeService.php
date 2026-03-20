<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Service;

use Lefteris\EverypayTask\Domain\Enum\ChargeStatus;
use Lefteris\EverypayTask\Domain\Exception\NotFoundException;
use Lefteris\EverypayTask\Domain\Exception\PaymentFailedException;
use Lefteris\EverypayTask\Model\Charge;
use Lefteris\EverypayTask\Provider\PaymentProviderFactory;
use Lefteris\EverypayTask\Repository\ChargeRepositoryInterface;
use Lefteris\EverypayTask\Repository\MerchantRepositoryInterface;
use Ramsey\Uuid\Uuid;

class ChargeService
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly ChargeRepositoryInterface   $chargeRepository,
        private readonly PaymentProviderFactory      $providerFactory,
    ) {}

    /**
     * @throws NotFoundException        When no merchant matches the given API key.
     * @throws PaymentFailedException   When the PSP declines the charge.
     */
    public function charge(ChargeRequestDTO $dto): ChargeResponseDTO
    {
        $merchant = $this->merchantRepository->findByApiKey($dto->apiKey);

        if ($merchant === null) {
            throw new NotFoundException('Merchant not found for the provided API key');
        }

        $provider  = $this->providerFactory->make($merchant);
        $chargeId  = Uuid::uuid4()->toString();
        $now       = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $transactionId = $provider->charge($dto);
            $status        = ChargeStatus::Succeeded->value;
        } catch (PaymentFailedException $e) {
            $this->chargeRepository->save(new Charge(
                id:            $chargeId,
                merchantId:    $merchant->id,
                amount:        $dto->amount,
                currency:      $dto->currency,
                status:        ChargeStatus::Failed->value,
                transactionId: null,
                createdAt:     $now,
            ));

            throw $e;
        }

        $this->chargeRepository->save(new Charge(
            id:            $chargeId,
            merchantId:    $merchant->id,
            amount:        $dto->amount,
            currency:      $dto->currency,
            status:        $status,
            transactionId: $transactionId,
            createdAt:     $now,
        ));

        return new ChargeResponseDTO(
            chargeId:      $chargeId,
            status:        $status,
            transactionId: $transactionId,
            amount:        $dto->amount,
            currency:      $dto->currency,
        );
    }
}
