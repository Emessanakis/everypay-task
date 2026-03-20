<?php

declare(strict_types=1);

namespace Lefteris\EverypayTask\Infrastructure;

/**
 * Minimal SMTP email sender using PHP streams.
 *
 * Supports SMTP AUTH LOGIN (Mailtrap, standard SMTP relays).
 * Credentials are injected from .env at construction time.
 */
class SimpleEmailSender implements EmailSenderInterface
{
    public function __construct(
        private readonly string $host,
        private readonly int    $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $from,
        private readonly string $fromName,
    ) {}

    /**
     * @throws \RuntimeException On SMTP connection or protocol errors.
     */
    public function send(string $to, string $subject, string $body): void
    {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 10);

        if ($socket === false) {
            throw new \RuntimeException("Could not connect to SMTP server [{$this->host}:{$this->port}]: $errstr ($errno)");
        }

        try {
            $this->expect($socket, '220');
            $this->command($socket, "EHLO {$this->host}", '250');
            $this->command($socket, 'AUTH LOGIN', '334');
            $this->command($socket, base64_encode($this->username), '334');
            $this->command($socket, base64_encode($this->password), '235');
            $this->command($socket, "MAIL FROM:<{$this->from}>", '250');
            $this->command($socket, "RCPT TO:<{$to}>", '250');
            $this->command($socket, 'DATA', '354');

            $message  = "From: {$this->fromName} <{$this->from}>\r\n";
            $message .= "To: <{$to}>\r\n";
            $message .= "Subject: {$subject}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= $body;
            $message .= "\r\n.";

            $this->command($socket, $message, '250');
            $this->command($socket, 'QUIT', '221');
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function command(mixed $socket, string $command, string $expectedCode): string
    {
        fwrite($socket, $command . "\r\n");

        return $this->expect($socket, $expectedCode);
    }

    /** @param resource $socket */
    private function expect(mixed $socket, string $expectedCode): string
    {
        $response = '';

        while ($line = fgets($socket, 512)) {
            $response .= $line;
            // The 4th char is a space on the last line of a multi-line response.
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if (!str_starts_with($response, $expectedCode)) {
            throw new \RuntimeException("SMTP error — expected {$expectedCode}, got: {$response}");
        }

        return $response;
    }
}
