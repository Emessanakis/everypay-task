<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Infrastructure;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SymfonyMailerSender implements EmailSenderInterface
{
    private readonly Mailer $mailer;

    public function __construct(
        string $dsn,
        private readonly string $from,
        private readonly string $fromName,
    ) {
        $this->mailer = new Mailer(Transport::fromDsn($dsn));
    }

    public function send(string $to, string $subject, string $body): void
    {
        $email = (new Email())
            ->from(new Address($this->from, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->text($body);

        $this->mailer->send($email);
    }
}
