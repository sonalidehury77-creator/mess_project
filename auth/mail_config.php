<?php

/**
 * 🌐 auth/mail_config.php
 * 
 * PHPMailer Configuration & OTP Mailing Utility
 * Smart Hostel Mess Management System
 */

// 1. IMPORT PHPMAILER CORE CLASSES
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends a secure, professionally styled HTML OTP email to a resident for password recovery.
 * 
 * @param string $email The recipient's email address.
 * @param string $name The recipient's name (for personalization).
 * @param string|int $otp The generated 6-digit One-Time Password.
 * @return bool True if transmission succeeds, false otherwise.
 */
function sendOTPEmail($email, $name, $otp)
{

    // ==========================================
    // ⚙️ EDIT YOUR SMTP CREDENTIALS HERE
    // ==========================================
    $smtp_host     = 'smtp.gmail.com';
    $smtp_port     = 587;
    $smtp_username = 'sonalidehury777@gmail.com';
    $smtp_password = 'bzys xtjm jzid qztx'; // 16-character Google App Password here
    $sender_name   = 'Hostel Mess Administration';
    // ==========================================

    $mail = new PHPMailer(true);

    try {
        // Server Settings
        $mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'sonalidehury777@gmail.com';
$mail->Password = 'bzys xtjm jzid qztx';

$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->SMTPAutoTLS = true;
$mail->Timeout = 60;

$mail->SMTPOptions = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];

        // Recipients
        $mail->setFrom($smtp_username, $sender_name);
        $email = trim($email);
        $name  = trim($name);

        $mail->clearAddresses();
        $mail->addAddress($email, $name);

        // Content Styling & Format Setup
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML(true);
        $mail->Subject = 'Hostel Mess Portal Password Reset OTP';

        // Beautiful Institutional Raw HTML Email Body
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset OTP</title>
        </head>
        <body style=\"margin: 0; padding: 0; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155;\">
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 550px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;'>
                <!-- Header Banner -->
                <tr>
                    <td bgcolor='#1e3a8a' style='padding: 32px 24px; text-align: center;'>
                        <h1 style='margin: 0; color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;'>CAMPUS DINING</h1>
                        <p style='margin: 4px 0 0 0; color: #ca8a04; font-size: 11px; font-weight: 700; tracking: 0.1em; text-transform: uppercase;'>Smart Mess Management Portal</p>
                    </td>
                </tr>
                <!-- Content Body -->
                <tr>
                    <td style='padding: 40px 32px;'>
                        <h2 style='margin: 0 0 16px 0; color: #1e3a8a; font-size: 18px; font-weight: 700;'>Password Verification Request</h2>
                        <p style='margin: 0 0 24px 0; font-size: 13px; line-height: 1.6; color: #475569;'>Hello " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</p>
                        <p style='margin: 0 0 24px 0; font-size: 13px; line-height: 1.6; color: #475569;'>A request was made to update your credentials for your resident profile. Use the verification parameter down below to authorize this lifecycle change:</p>
                        
                        <!-- Colored Box Displaying OTP Token -->
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin: 24px 0;'>
                            <tr>
                                <td align='center' bgcolor='#f0f4f8' style='padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e1;'>
                                    <span style='font-family: Monaco, Consolas, monospace; font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #1e3a8a;'>" . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . "</span>
                                </td>
                            </tr>
                        </table>

                        <p style='margin: 0 0 24px 0; font-size: 12px; line-height: 1.5; color: #64748b; font-style: italic;'>
                            ⚠️ This token calculation expires strictly in <strong>10 minutes</strong>. 
                        </p>
                        <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0;'>
                        <p style='margin: 0; font-size: 11px; line-height: 1.5; color: #94a3b8;'>
                            If you did not initiate this recovery cycle, safely disregard this communication. No administrative security configurations have been altered on your profile.
                        </p>
                    </td>
                </tr>
                <!-- Footer Metadata -->
                <tr>
                    <td bgcolor='#f8fafc' style='padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0;'>
                        <p style='margin: 0; font-size: 10px; color: #64748b; font-weight: 500; letter-spacing: 0.05em;'>&copy; 2026 Hostel Mess Management System. Secure Digital Infrastructure.</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        $mail->AltBody = "Campus Dining Portal - Password Reset OTP\n\nHello " . $name . ",\n\nYour One-Time Password is: " . $otp . "\n\nThis verification code remains valid for exactly 10 minutes.";

        if (!$mail->send()) {
            echo "<pre>";
            echo "Mail Error: " . $mail->ErrorInfo;
            exit;
        }
        return true;
    } catch (Exception $e) {
        die("<h2>PHPMailer Error</h2>" .
            "<b>Error:</b> " . $mail->ErrorInfo .
            "<br><br><b>Exception:</b> " . $e->getMessage());
    } catch (\Exception $e) {
        die($e->getMessage());
    }
}
