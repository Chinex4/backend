<?php


class EmailTemplate
{

  public function buildOtpHtml(string $fullname, string $otpCode, $emailLogo, $supportMail): string
  {
    return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Verify Email</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #0f0f0f; font-family: Arial, sans-serif;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0f0f0f;">
            <tr>
              <td align="center" style="padding: 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #1e1e1e; border-radius: 12px; width: 100%; max-width: 600px;">
                  
                  <!-- Logo Section -->
                  <tr>
                    <td align="center" style="background-color: #1e1e1e; padding: 20px 0;">
                      <img src="' . $emailLogo . '" alt="Logo" width="200" style="display: block; max-width: 100%; height: auto;" />
                    </td>
                  </tr>
    
                  <!-- Content Section -->
                  <tr>
                    <td align="center" style="padding: 30px 20px; text-align: center;">
                      <h1 style="color: #ffffff; font-size: 28px; margin-bottom: 20px;">Verify Your Email Address</h1>
                      <p style="font-size: 16px; color: #cccccc; line-height: 1.6; margin: 0;">
                        Dear ' . htmlspecialchars($fullname) . ',<br><br>
                        Please use the code below to complete your registration.
                      </p>
                      <div style="font-size: 36px; font-weight: bold; color: #e5f455; margin: 30px 0;">
                        ' . htmlspecialchars($otpCode) . '
                      </div>
                      <p style="font-size: 14px; color: #999999; margin: 20px 0;">
                        Code is valid for 10 minutes. Please do not share it with anyone.<br>
                        If you have any questions, contact us at <a href="mailto:' . $supportMail . '" style="color: #e5f455;">' . $supportMail . '</a>.
                      </p>
                    </td>
                  </tr>
    
                  <!-- Footer -->
                  <tr>
                    <td align="center" style="padding: 20px;">
                      <a href="#" style="font-size: 12px; color: #03b1a0; text-decoration: none;">Unsubscribe</a>
                    </td>
                  </tr>
    
                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>';
  }
  public static function buildWelcomeHtml(string $recipientEmail, $appName, $siteAddress): string
  {
    return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8" />
          <title>Welcome to ' . $appName . '</title>
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
          <style>
            body {
              font-family: Ubuntu, Helvetica, Arial, sans-serif;
              background-color: #ffffff;
              margin: 0;
              padding: 0;
              color: #000000;
            }
            .container {
              max-width: 750px;
              margin: 0 auto;
              padding: 20px;
            }
            .btn {
              display: inline-block;
              background-color: #b9f641;
              color: #111;
              padding: 12px 24px;
              border-radius: 22px;
              text-decoration: none;
              font-size: 14px;
              font-weight: bold;
            }
            .footer {
              color: gray;
              font-size: 13px;
              text-align: center;
              padding: 24px 10px;
            }
            .social-icons img {
              margin: 0 5px;
              width: 24px;
              height: 24px;
            }
          </style>
        </head>
        <body>
          <div class="container">
            <img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/welcome/email-top-banner_en-US.png" alt="Welcome Banner" width="100%" style="display:block;" />

            <h2>Welcome <a href="mailto:' . htmlspecialchars($recipientEmail) . '">' . htmlspecialchars($recipientEmail) . '</a>,</h2>
            <p>You\'ve just made the smartest move by joining <strong>' . $appName . ' – Globally Top Rated Crypto Exchange!</strong> 🏆</p>
            <p>Now, let’s get you started with <strong>over 8,000+ USDT in Rewards</strong> – because who doesn’t love more crypto?</p>
            <a href="https://bitunix.com/signup-bonus" target="_blank">
              <img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/welcome/welcome-gift_en-US.png" alt="Welcome Bonus" width="100%" style="display:block;" />
            </a>
            <h3>Secure your account now</h3>
            <p>Still running around without Google Authenticator? 🤯<br />
            Protect your funds and account from unauthorized access.</p>
            <p>👉 <a href="https://bitunix.com/google-auth" style="color:#86bb00;">Enable Google Authenticator</a></p>
            <div style="background-color: #f9f9f9; padding: 24px; border-radius: 4px; text-align: center;">
              <h3>What Next?</h3>
              <p>Join the Crypto Waves with Bitunix!</p>
              <a href="https://bitunix.com/start-trading" class="btn">Claim Your Rewards & Start Trading</a>
            </div>
            <p style="color: gray;">HODL strong!<br />— Team Bitunix</p>
            <hr style="border-top: 1px dashed #ebebeb;" />
            <p style="text-align: center;">
              <img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/welcome/email-btm-banner_en-US.png" alt="Footer Banner" width="100%" />
            </p>
            <div class="footer">
              <img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/welcome/trustpilot.png" alt="Trustpilot" width="382" style="margin-bottom: 20px;" />
              <p>Download App & Follow Us</p>
              <div class="social-icons">
                <a href="#"><img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/twitter.png" alt="Twitter" /></a>
                <a href="#"><img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/tg-icon.png" alt="Telegram" /></a>
                <a href="#"><img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/m.png" alt="Medium" /></a>
                <a href="#"><img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/fb.png" alt="Facebook" /></a>
                <a href="#"><img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/in.png" alt="LinkedIn" /></a>
                <a href="#"><img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/ins.png" alt="Instagram" /></a>
                <a href="#"><img src="https://bitunix-public.oss-ap-northeast-1.aliyuncs.com/email/youtube.png" alt="YouTube" /></a>
              </div>

              <div style="margin-top: 20px;">© 2022 - ' . date('Y') . ' ' . $siteAddress . ' All rights reserved</div>
            </div>
          </div>
        </body>
        </html>';
  }
  public function buildResetPasswordHtml(string $fullname, string $resetToken, string $email, string $token, string $emailLogo, string $supportMail): string
  {
    $resetLink = appLink . 'verifyResetPassword?T=' . urlencode($resetToken) . '&m=' . urlencode($email) . '&n=' . urlencode($token);

    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Reset Your Password</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #0f0f0f; font-family: Arial, sans-serif;">
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0f0f0f;">
        <tr>
          <td align="center" style="padding: 20px;">
            <table width="600" cellpadding="0" cellspacing="0" style="background-color: #1e1e1e; border-radius: 12px; width: 100%; max-width: 600px;">
              
              <!-- Logo Section -->
              <tr>
                <td align="center" style="background-color: #1e1e1e; padding: 20px 0;">
                  <img src="' . $emailLogo . '" alt="Logo" width="200" style="display: block; max-width: 100%; height: auto;" />
                </td>
              </tr>

              <!-- Content Section -->
              <tr>
                <td align="center" style="padding: 30px 20px; text-align: center;">
                  <h1 style="color: #ffffff; font-size: 28px; margin-bottom: 20px;">Reset Your Password</h1>
                  <p style="font-size: 16px; color: #cccccc; line-height: 1.6; margin: 0;">
                    Dear ' . htmlspecialchars($fullname) . ',<br><br>
                    You requested to reset your password. Click the button below to proceed:
                  </p>
                  <div style="margin: 30px 0;">
                    <a href="' . $resetLink . '" 
                       style="padding: 12px 24px; background-color: #00bdff; color: #fff; text-decoration: none; border-radius: 6px; font-size: 16px;">
                      Reset Password
                    </a>
                  </div>
                  <p style="font-size: 14px; color: #999999; margin: 20px 0;">
                    This link is valid for 24 hours.<br>
                    If you did not request a password reset, please ignore this email.<br>
                    For any questions, contact us at 
                    <a href="mailto:' . $supportMail . '" style="color: #e5f455;">' . $supportMail . '</a>.
                  </p>
                </td>
              </tr>

              <!-- Footer -->
              <tr>
                <td align="center" style="padding: 20px;">
                  <a href="#" style="font-size: 12px; color: #03b1a0; text-decoration: none;">Unsubscribe</a>
                </td>
              </tr>

            </table>
          </td>
        </tr>
      </table>
    </body>
    </html>';
  }
  public function buildPasswordChangedHtml(string $fullname, string $emailLogo, string $supportMail): string
  {
      return '
      <!DOCTYPE html>
      <html lang="en">
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Changed</title>
      </head>
      <body style="margin: 0; padding: 0; background-color: #0f0f0f; font-family: Arial, sans-serif;">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0f0f0f;">
          <tr>
            <td align="center" style="padding: 20px;">
              <table width="600" cellpadding="0" cellspacing="0" style="background-color: #1e1e1e; border-radius: 12px; width: 100%; max-width: 600px;">
                
                <!-- Logo Section -->
                <tr>
                  <td align="center" style="background-color: #1e1e1e; padding: 20px 0;">
                    <img src="' . $emailLogo . '" alt="Logo" width="200" style="display: block; max-width: 100%; height: auto;" />
                  </td>
                </tr>
  
                <!-- Content Section -->
                <tr>
                  <td align="center" style="padding: 30px 20px; text-align: center;">
                    <h1 style="color: #ffffff; font-size: 28px; margin-bottom: 20px;">Your Password Has Been Changed</h1>
                    <p style="font-size: 16px; color: #cccccc; line-height: 1.6; margin: 0;">
                      Dear ' . htmlspecialchars($fullname) . ',<br><br>
                      This is a confirmation that your account password was successfully updated.
                    </p>
                    <p style="font-size: 14px; color: #999999; margin: 30px 0;">
                      If you did not perform this action, please contact our support team immediately.<br>
                      <a href="mailto:' . $supportMail . '" style="color: #e5f455;">' . $supportMail . '</a>
                    </p>
                  </td>
                </tr>
  
                <!-- Footer -->
                <tr>
                  <td align="center" style="padding: 20px;">
                    <a href="#" style="font-size: 12px; color: #03b1a0; text-decoration: none;">Unsubscribe</a>
                  </td>
                </tr>
  
              </table>
            </td>
          </tr>
        </table>
      </body>
      </html>';
  }
  
}