<?php

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/Templates/EmailTemplate.php';

class EmailSender
{
    private Mailer $mailer;
    private string $fromEmail;
    private string $supportEmail;
    private string $appName;
    private string $appLiink;
    private string $logo;
    private $emailTemplate;

    public function __construct()
    {
        $this->fromEmail = sitemail;
        $this->supportEmail = sitemail;
        $this->appName = sitename;
        $this->appLiink = appLink;
        $this->logo = emailLogo;

        $dsn = SMTP_DSN;
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
        $this->emailTemplate = new EmailTemplate();
    }

    public function sendOtpEmail(string $recipientEmail, string $fullname, string $otpCode): bool
    {
        $html = $this->emailTemplate->buildOtpHtml($fullname, $otpCode, emailLogo, sitemail);
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($recipientEmail)
            ->subject('Your ' . $this->appName . ' Verification Code')
            ->text('Your verification code is: ' . $otpCode)
            ->html($html);
        try {
            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            error_log('Email send failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendOtpForChangePassword(string $recipientEmail, string $fullname, string $otpCode): bool
{
    $html = $this->emailTemplate->buildOtpForChangePasswordHtml($fullname, $otpCode, emailLogo, sitemail);
    $email = (new Email())
        ->from($this->fromEmail)
        ->to($recipientEmail)
        ->subject('Your ' . $this->appName . ' Password Change Verification Code')
        ->text('Your password change verification code is: ' . $otpCode)
        ->html($html);
    try {
        $this->mailer->send($email);
        return true;
    } catch (\Throwable $e) {
        error_log('Change password OTP email send failed: ' . $e->getMessage());
        return false;
    }
}

    public function sendOtpForLogin(string $recipientEmail, string $fullname, string $otpCode): bool
    {
        $html = $this->emailTemplate->buildOtpForLoginHtml($fullname, $otpCode, emailLogo, sitemail);
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($recipientEmail)
            ->subject('Your ' . $this->appName . ' Login Verification Code')
            ->text('Your verification code is: ' . $otpCode)
            ->html($html);
        try {
            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            error_log('Email send failed: ' . $e->getMessage());
            return false;
        }
    }
    public function sendWelcomEmail(string $recipientEmail): bool
    {
        $html = $this->emailTemplate::buildWelcomeHtml($recipientEmail, $this->appName, $this->appLiink);
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($recipientEmail)
            ->subject('Welcome to ' . $this->appName . ', Claim Over 8,000+ USDT Worth of Newcomer
            Rewards!')
            ->text('')
            ->html($html);
        try {
            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            error_log('Email send failed: ' . $e->getMessage());
            return false;
        }
    }
    public function sendResetPasswordEmail(string $fullname, string $resetToken, string $recipientEmail, string $accToken): bool
    {
        $html = $this->emailTemplate->buildResetPasswordHtml($fullname, $resetToken, $recipientEmail, $accToken, $this->logo, $this->supportEmail);
        $email = (new Email())
        ->from($this->fromEmail)
        ->to($recipientEmail)
        ->subject('Reset Your Password - ' . $this->appName)
        ->html($html); 
        try {
            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            error_log('Email send failed: ' . $e->getMessage());
            return false;
        }
    }
    public function sendPasswordChangedEmail(string $fullname, string $recipientEmail): bool
{
    $html = $this->emailTemplate->buildPasswordChangedHtml($fullname, $this->logo, $this->supportEmail);

    $email = (new Email())
        ->from($this->fromEmail)
        ->to($recipientEmail)
        ->subject('Your Password Has Been Changed - ' . $this->appName)
        ->html($html);

    try {
        $this->mailer->send($email);
        return true;
    } catch (\Throwable $e) {
        error_log('Password change email failed: ' . $e->getMessage());
        return false;
    }
}

    
}
