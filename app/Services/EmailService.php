<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Twig\Environment;

class EmailService
{
    private PHPMailer $mailer;
    private Environment $twig;
    private array $config;

    public function __construct(?Environment $twig = null)
    {
        $this->mailer = new PHPMailer(true);
        $this->twig = $twig ?? $this->getDefaultTwig();
        $this->config = require __DIR__ . '/../../config/email.php';
        $this->configureMailer();
    }

    private function getDefaultTwig(): Environment
    {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../templates/emails');
        return new Environment($loader, ['cache' => false]);
    }

    private function configureMailer(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['host'] ?? $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->config['username'] ?? $_ENV['SMTP_USERNAME'] ?? '';
        $this->mailer->Password = $this->config['password'] ?? $_ENV['SMTP_PASSWORD'] ?? '';
        $this->mailer->SMTPSecure = $this->config['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $this->config['port'] ?? $_ENV['SMTP_PORT'] ?? 587;

        $this->mailer->setFrom(
            $this->config['from_email'] ?? $_ENV['MAIL_FROM'] ?? 'noreply@example.com',
            $this->config['from_name'] ?? $_ENV['MAIL_FROM_NAME'] ?? 'Ecommerce Store'
        );
    }

    public function sendOrderConfirmation(array $orderData): bool
    {
        $html = $this->twig->render('order-confirmation.twig', $orderData);

        $this->mailer->clearAllRecipients();
        $this->mailer->addAddress($orderData['customer_email'], $orderData['customer_name']);
        $this->mailer->Subject = "Order Confirmation #{$orderData['order_number']}";
        $this->mailer->isHTML(true);
        $this->mailer->Body = $html;

        return $this->mailer->send();
    }

    public function sendOrderStatusUpdate(array $orderData, string $oldStatus, string $newStatus): bool
    {
        $html = $this->twig->render('order-status-update.twig', array_merge($orderData, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]));

        $this->mailer->clearAllRecipients();
        $this->mailer->addAddress($orderData['customer_email'], $orderData['customer_name']);
        $this->mailer->Subject = "Order #{$orderData['order_number']} Status Update";
        $this->mailer->isHTML(true);
        $this->mailer->Body = $html;

        return $this->mailer->send();
    }

    public function sendWelcomeEmail(array $userData): bool
    {
        $html = $this->twig->render('welcome.twig', $userData);

        $this->mailer->clearAllRecipients();
        $this->mailer->addAddress($userData['email'], $userData['first_name']);
        $this->mailer->Subject = "Welcome to " . ($_ENV['APP_NAME'] ?? 'Store');
        $this->mailer->isHTML(true);
        $this->mailer->Body = $html;

        return $this->mailer->send();
    }

    public function sendContactFormNotification(array $contactData): bool
    {
        $html = $this->twig->render('contact-notification.twig', $contactData);

        $this->mailer->clearAllRecipients();
        $this->mailer->addAddress($this->config['admin_email'] ?? $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com');
        $this->mailer->Subject = "New Contact Form: " . ($contactData['subject'] ?? 'No Subject');
        $this->mailer->isHTML(true);
        $this->mailer->Body = $html;

        return $this->mailer->send();
    }

    public function send(array $to, string $subject, string $template, array $data = []): bool
    {
        $html = $this->twig->render($template, $data);

        $this->mailer->clearAllRecipients();
        $this->mailer->addAddress($to['email'], $to['name'] ?? '');
        $this->mailer->Subject = $subject;
        $this->mailer->isHTML(true);
        $this->mailer->Body = $html;

        return $this->mailer->send();
    }
}