<?php
/**
 * Mail - PHPMailer SMTP wrapper
 */

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

class Mail
{
    private static function createMailer(): PHPMailer
    {
        $cfg    = require APP_PATH . '/config/mail.php';
        $mailer = new PHPMailer(true);

        $mailer->isSMTP();
        $mailer->Host       = $cfg['host'];
        $mailer->SMTPAuth   = true;
        $mailer->Username   = $cfg['username'];
        $mailer->Password   = $cfg['password'];
        $mailer->SMTPSecure = $cfg['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port       = $cfg['port'];
        $mailer->SMTPDebug  = $cfg['debug'];
        $mailer->Debugoutput = function ($str) {
            error_log('SMTP: ' . trim($str));
        };
        $mailer->CharSet    = 'UTF-8';
        $mailer->setFrom($cfg['from_email'], $cfg['from_name']);

        return $mailer;
    }

    /**
     * Send a plain text + HTML email
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainBody = ''): bool
    {
        if (!empty($_ENV['MAIL_DISABLED'])) {
            error_log("Mail disabled (trial mode): would send '{$subject}' to {$toEmail}");
            return true;
        }

        try {
            $mailer = self::createMailer();
            $mailer->addAddress($toEmail, $toName);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body    = $htmlBody;
            $mailer->AltBody = $plainBody ?: strip_tags($htmlBody);
            $mailer->send();
            return true;
        } catch (MailException $e) {
            error_log('Mail error: ' . $e->getMessage());
            return false;
        }
    }

    // ── Template wrappers ─────────────────────────────────────

    public static function sendEmailVerification(string $email, string $name, string $token): bool
    {
        $link    = APP_URL . '/verify-email.php?token=' . $token;
        $subject = '[ICALGC 2026] Please Verify Your Email Address';
        $html    = self::wrapTemplate('Email Verification', "
            <p>Dear {$name},</p>
            <p>Thank you for registering for ICALGC 2026. Please click the button below to verify your email address:</p>
            <p style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#003087;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>Verify Email</a>
            </p>
            <p>Or copy this link: <a href='{$link}'>{$link}</a></p>
            <p>This link expires in 24 hours.</p>
        ");
        return self::send($email, $name, $subject, $html);
    }

    public static function sendPasswordReset(string $email, string $name, string $token): bool
    {
        $link    = APP_URL . '/reset-password.php?token=' . $token;
        $subject = '[ICALGC 2026] Password Reset Request';
        $html    = self::wrapTemplate('Password Reset', "
            <p>Dear {$name},</p>
            <p>We received a request to reset your password. Click the button below to proceed:</p>
            <p style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#003087;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>Reset Password</a>
            </p>
            <p>Or copy this link: <a href='{$link}'>{$link}</a></p>
            <p>This link expires in 1 hour. If you did not request a password reset, please ignore this email.</p>
        ");
        return self::send($email, $name, $subject, $html);
    }

    public static function sendPaperSubmitted(string $email, string $name, string $paperCode, string $paperTitle): bool
    {
        $subject = '[ICALGC 2026] Paper Submission Received – ' . $paperCode;
        $html    = self::wrapTemplate('Paper Submitted', "
            <p>Dear {$name},</p>
            <p>Your paper has been successfully submitted to ICALGC 2026.</p>
            <table style='border-collapse:collapse;width:100%;margin:20px 0;'>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Paper Code</td><td style='padding:8px;border:1px solid #ddd;'>{$paperCode}</td></tr>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Title</td><td style='padding:8px;border:1px solid #ddd;'>{$paperTitle}</td></tr>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Status</td><td style='padding:8px;border:1px solid #ddd;'>Submitted</td></tr>
            </table>
            <p>We will notify you once the screening process begins. You can track your paper status in your <a href='" . APP_URL . "/author/my-papers.php'>Author Dashboard</a>.</p>
        ");
        return self::send($email, $name, $subject, $html);
    }

    public static function sendReviewAssignment(string $email, string $name, string $paperCode, string $paperTitle, string $dueDate): bool
    {
        $subject = '[ICALGC 2026] New Paper Assigned for Review – ' . $paperCode;
        $link    = APP_URL . '/reviewer/dashboard.php';
        $html    = self::wrapTemplate('New Review Assignment', "
            <p>Dear {$name},</p>
            <p>A new paper has been assigned to you for review.</p>
            <table style='border-collapse:collapse;width:100%;margin:20px 0;'>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Paper Code</td><td style='padding:8px;border:1px solid #ddd;'>{$paperCode}</td></tr>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Title</td><td style='padding:8px;border:1px solid #ddd;'>{$paperTitle}</td></tr>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Review Deadline</td><td style='padding:8px;border:1px solid #ddd;'>{$dueDate}</td></tr>
            </table>
            <p style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#003087;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>Go to Reviewer Dashboard</a>
            </p>
        ");
        return self::send($email, $name, $subject, $html);
    }

    public static function sendReviewResult(string $email, string $name, string $paperCode, string $paperTitle, string $decision): bool
    {
        $subject = '[ICALGC 2026] Review Result for Your Paper – ' . $paperCode;
        $link    = APP_URL . '/author/paper-detail.php?code=' . $paperCode;
        $html    = self::wrapTemplate('Review Result Available', "
            <p>Dear {$name},</p>
            <p>The review result for your paper is now available.</p>
            <table style='border-collapse:collapse;width:100%;margin:20px 0;'>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Paper Code</td><td style='padding:8px;border:1px solid #ddd;'>{$paperCode}</td></tr>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Title</td><td style='padding:8px;border:1px solid #ddd;'>{$paperTitle}</td></tr>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Decision</td><td style='padding:8px;border:1px solid #ddd;'>{$decision}</td></tr>
            </table>
            <p style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#003087;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>View Details</a>
            </p>
        ");
        return self::send($email, $name, $subject, $html);
    }

    public static function sendAccepted(string $email, string $name, string $paperCode, string $paperTitle): bool
    {
        $subject = '[ICALGC 2026] Congratulations! Your Paper Has Been Accepted – ' . $paperCode;
        $link    = APP_URL . '/author/my-papers.php';
        $html    = self::wrapTemplate('Paper Accepted', "
            <p>Dear {$name},</p>
            <p>We are pleased to inform you that your paper has been <strong>accepted</strong> for presentation at ICALGC 2026.</p>
            <table style='border-collapse:collapse;width:100%;margin:20px 0;'>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Paper Code</td><td style='padding:8px;border:1px solid #ddd;'>{$paperCode}</td></tr>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Title</td><td style='padding:8px;border:1px solid #ddd;'>{$paperTitle}</td></tr>
            </table>
            <p>Please log in to your dashboard to download your Certificate of Acceptance.</p>
            <p style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#198754;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>View My Papers</a>
            </p>
        ");
        return self::send($email, $name, $subject, $html);
    }

    public static function sendPublished(string $email, string $name, string $paperCode, string $paperTitle): bool
    {
        $subject = '[ICALGC 2026] Your Paper Has Been Published – ' . $paperCode;
        $link    = APP_URL . '/publication.php';
        $html    = self::wrapTemplate('Paper Published', "
            <p>Dear {$name},</p>
            <p>Your paper is now published in the ICALGC 2026 Publication Repository.</p>
            <table style='border-collapse:collapse;width:100%;margin:20px 0;'>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Paper Code</td><td style='padding:8px;border:1px solid #ddd;'>{$paperCode}</td></tr>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Title</td><td style='padding:8px;border:1px solid #ddd;'>{$paperTitle}</td></tr>
            </table>
            <p style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#0f5132;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>View Publication</a>
            </p>
        ");
        return self::send($email, $name, $subject, $html);
    }

    public static function sendCertificateReady(string $email, string $name, string $certTypeLabel, ?string $paperCode = null, string $role = 'author'): bool
    {
        $subject = '[ICALGC 2026] Your Certificate is Ready – ' . $certTypeLabel;
        $link    = APP_URL . '/' . ($role === 'reviewer' ? 'reviewer' : 'author') . '/certificates.php';
        $paperRow = $paperCode
            ? "<tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Paper Code</td><td style='padding:8px;border:1px solid #ddd;'>{$paperCode}</td></tr>"
            : '';
        $html    = self::wrapTemplate('Certificate Ready', "
            <p>Dear {$name},</p>
            <p>Your <strong>{$certTypeLabel}</strong> certificate has been issued and is ready to download.</p>
            <table style='border-collapse:collapse;width:100%;margin:20px 0;'>
                <tr><td style='padding:8px;border:1px solid #ddd;font-weight:bold;'>Certificate Type</td><td style='padding:8px;border:1px solid #ddd;'>{$certTypeLabel}</td></tr>
                {$paperRow}
            </table>
            <p style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#003087;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>Download Certificate</a>
            </p>
        ");
        return self::send($email, $name, $subject, $html);
    }

    // HTML email wrapper
    private static function wrapTemplate(string $heading, string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.1);">
      <tr><td style="background:linear-gradient(135deg,#003087,#c9a227);padding:30px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:24px;">ICALGC 2026</h1>
        <p style="color:rgba(255,255,255,.85);margin:5px 0 0;font-size:14px;">International Conference on ASEAN Languages in Global Contexts</p>
      </td></tr>
      <tr><td style="padding:30px;">
        <h2 style="color:#003087;margin-top:0;">{$heading}</h2>
        {$body}
        <hr style="border:none;border-top:1px solid #eee;margin:30px 0;">
        <p style="color:#999;font-size:12px;margin:0;">
          Faculty of Humanities, Srinakharinwirot University<br>
          114 Sukhumvit 23, Wattana, Bangkok 10110, Thailand<br>
          Email: <a href="mailto:icalgc2026@gmail.com" style="color:#003087;">icalgc2026@gmail.com</a>
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
}
