<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Console;

use Lefteris\EverypayTask\Domain\Exception\NotFoundException;
use Lefteris\EverypayTask\Service\ReportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendChargeReportCommand extends Command
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {
        parent::__construct('report:send');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Collect charges for a merchant and send a report via email')
            ->addOption(
                'merchant-id',
                null,
                InputOption::VALUE_REQUIRED,
                'UUID of the merchant'
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'Report start date (Y-m-d)',
                date('Y-m-d', strtotime('-7 days'))
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'Report end date (Y-m-d)',
                date('Y-m-d')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $merchantId = $input->getOption('merchant-id');

        if (empty($merchantId)) {
            $output->writeln('<error>--merchant-id is required</error>');
            return Command::FAILURE;
        }

        $from = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $input->getOption('from'));
        $to   = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $input->getOption('to'));

        if ($from === false || $to === false) {
            $output->writeln('<error>Invalid date format. Use Y-m-d (e.g. 2024-01-01)</error>');
            return Command::FAILURE;
        }

        // Include the full end day.
        $from = $from->setTime(0, 0, 0);
        $to   = $to->setTime(23, 59, 59);

        try {
            $this->reportService->sendReport($merchantId, $from, $to);

            $output->writeln(sprintf(
                '<info>Report sent for merchant %s (%s → %s)</info>',
                $merchantId,
                $from->format('Y-m-d'),
                $to->format('Y-m-d'),
            ));

            return Command::SUCCESS;
        } catch (NotFoundException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $output->writeln("<error>Unexpected error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
