<?php

return [
    'host' => $_ENV['SMTP_HOST'] ?? 'localhost',
    'port' => $_ENV['SMTP_PORT'] ?? 587,
    'username' => $_ENV['SMTP_USERNAME'] ?? '',
    'password' => $_ENV['SMTP_PASSWORD'] ?? '',
    'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
    'from_email' => $_ENV['MAIL_FROM'] ?? 'noreply@example.com',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Ecommerce Store',
    'admin_email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com',
];