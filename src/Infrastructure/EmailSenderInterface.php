<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Infrastructure;

interface EmailSenderInterface
{
    /**
     * @throws \RuntimeException On delivery failure.
     */
    public function send(string $to, string $subject, string $body): void;
}
