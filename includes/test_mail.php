<?php
// includes/test_mail.php
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$recipient = 'localhost@gmail.com'; // <- change to an email you can check

try {
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USER;
    $mail->Password   = MAIL_PASS;
    $mail->SMTPSecure = MAIL_SECURE; // 'tls'
    $mail->Port       = MAIL_PORT;

    // Recipients
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($recipient);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer test from SMS SYSTEM';
    $mail->Body    = '<p>This is a <strong>test</strong> email sent with PHPMailer on SMS SYSTEM.</p>';
    $mail->AltBody = 'This is a test email sent with PHPMailer on SMS SYSTEM.';

    // Optional: enable debugging output if something fails (0 = off, 2 = verbose)
    // $mail->SMTPDebug = 2;

    $mail->send();
    echo "SUCCESS: Test message sent to {$recipient}. Check your inbox (and spam).";
} catch (Exception $e) {
    echo "ERROR: Message could not be sent. Mailer Error: " . htmlspecialchars($mail->ErrorInfo);
}
