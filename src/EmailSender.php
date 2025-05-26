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
    private $emailTemplate;

    public function __construct()
    {
        $this->fromEmail = sitemail;
        $this->supportEmail = sitemail;
        $this->appName = sitename;

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



}
