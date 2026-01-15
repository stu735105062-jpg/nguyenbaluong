<?php
session_start();
require '../config/config.php';
require '../includes/PHPMailer/PHPMailer.php';
require '../includes/PHPMailer/SMTP.php';
require '../includes/PHPMailer/Exception.php';
require '../includes/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        // Direct link to reset password form (no token)
        $reset_link = "http://localhost/smss/public/reset_password.php?email=" . urlencode($email);

        // Send Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = MAIL_SECURE;
            $mail->Port       = MAIL_PORT;

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset - University SMS';

            $mail->Body = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background: #f4f6f8; }
                    .box {
                        max-width: 480px; margin: 40px auto; background: #fff;
                        border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                        overflow: hidden; text-align: center; padding: 25px;
                    }
                    .link {
                        display: inline-block; margin: 20px 0; padding: 12px 25px;
                        background: #0d6efd; text-decoration: none;
                        border-radius: 8px; font-weight: bold;
                    }
                    .footer {
                        text-align: center; padding: 10px; font-size: 12px;
                        background: #f8f9fa; color: #777;
                    }
                </style>
            </head>
            <body>
                <div class="box">
                    <h2>University SMS - Password Reset</h2>
                    <p>Hello,</p>
                    <p>We received a request to reset your password.</p>
                    <a href="' . $reset_link . '" class="link" style="background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:8px;padding:12px 25px;font-weight:bold;display:inline-block;">Set Your New Password</a>
                    <div class="footer">© ' . date("Y") . ' University SMS</div>
                </div>
            </body>
            </html>';

            $mail->send();
            $_SESSION['success'] = "✅ Password reset link sent successfully to your email.";

        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        $_SESSION['error'] = "❌ Email not found in our records.";
    }

    // Stay on the same page and show message
    header("Location: ../public/reset_request.php");
    exit;
}
?>