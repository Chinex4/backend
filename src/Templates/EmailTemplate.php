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
                      <img src="'.$emailLogo.'" alt="Logo" width="200" style="display: block; max-width: 100%; height: auto;" />
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

}