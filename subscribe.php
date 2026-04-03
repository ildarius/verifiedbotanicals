<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib/env.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$emailInput = $_POST['email'] ?? '';
$email = filter_var(trim((string) $emailInput), FILTER_VALIDATE_EMAIL);

if ($email === false) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Enter a valid email address.']);
    exit;
}

try {
    $config = loadEnv(dirname(__DIR__) . '/.env');

    $requiredKeys = [
        'SMTP_HOST',
        'SMTP_PORT',
        'SMTP_USERNAME',
        'SMTP_PASSWORD',
        'SMTP_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
        'MAIL_TO_ADDRESS',
    ];

    foreach ($requiredKeys as $key) {
        if (empty($config[$key])) {
            throw new RuntimeException("Missing required env value: {$key}");
        }
    }

    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $config['SMTP_HOST'];
    $mailer->Port = (int) $config['SMTP_PORT'];
    $mailer->SMTPAuth = true;
    $mailer->Username = $config['SMTP_USERNAME'];
    $mailer->Password = $config['SMTP_PASSWORD'];

    if ($config['SMTP_ENCRYPTION'] === 'ssl') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mailer->setFrom($config['MAIL_FROM_ADDRESS'], $config['MAIL_FROM_NAME']);
    $mailer->addAddress($config['MAIL_TO_ADDRESS']);
    $mailer->addReplyTo($email);
    $mailer->Subject = 'New Verified Botanicals subscriber';
    $mailer->Body = "A new subscriber joined the list.\n\nEmail: {$email}";
    $mailer->AltBody = $mailer->Body;

    $mailer->send();

    echo json_encode([
        'ok' => true,
        'message' => 'Thanks. You are on the list.',
    ]);
} catch (Exception | RuntimeException $exception) {
    http_response_code(500);
    error_log('Subscribe form error: ' . $exception->getMessage());

    echo json_encode([
        'ok' => false,
        'message' => 'Subscription failed. Please try again shortly.',
    ]);
}
