<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface; // Monolog implements this interface

class MailerService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function sendWelcomeEmail(string $recipientEmail, string $username): bool
    {
        // In a real application, you'd use a proper mail library here (e.g., PHPMailer, Symfony Mailer)
        $this->logger->info("Sending welcome email to {$recipientEmail} for user {$username}");
        // Simulate email sending
        if (rand(0, 1)) { // Simulate success/failure
            $this->logger->info("Welcome email sent successfully to {$recipientEmail}");
            return true;
        } else {
            $this->logger->error("Failed to send welcome email to {$recipientEmail}");
            return false;
        }
    }
}
