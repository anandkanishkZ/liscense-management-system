<?php
/**
 * Zwicky Technology License Management System
 * Email Notification Class
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

class LMSEmailNotifier {
    private $logger;
    
    public function __construct() {
        $this->logger = new LMSLogger();
    }
    
    /**
     * Send email notification
     */
    public function sendEmail($to, $subject, $message, $headers = []) {
        try {
            // Check if SMTP is configured
            if (LMS_SMTP_HOST && LMS_SMTP_USERNAME) {
                return $this->sendSMTPEmail($to, $subject, $message, $headers);
            } else {
                return $this->sendPHPMailEmail($to, $subject, $message, $headers);
            }
        } catch (Exception $e) {
            $this->logger->error("Email sending failed", [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Send email using PHP's mail() function
     */
    private function sendPHPMailEmail($to, $subject, $message, $headers = []) {
        $default_headers = [
            'From: ' . LMS_EMAIL_FROM_NAME . ' <' . LMS_EMAIL_FROM . '>',
            'Reply-To: ' . LMS_EMAIL_FROM,
            'X-Mailer: Zwicky License Manager v' . LMS_VERSION,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $all_headers = array_merge($default_headers, $headers);
        $header_string = implode("\r\n", $all_headers);
        
        $result = mail($to, $subject, $message, $header_string);
        
        if ($result) {
            $this->logger->info("Email sent successfully", [
                'to' => $to,
                'subject' => $subject,
                'method' => 'php_mail'
            ]);
        } else {
            $this->logger->error("PHP mail() function failed", [
                'to' => $to,
                'subject' => $subject
            ]);
        }
        
        return $result;
    }
    
    /**
     * Send email using SMTP (basic implementation)
     */
    private function sendSMTPEmail($to, $subject, $message, $headers = []) {
        // Basic SMTP implementation
        // For production, consider using PHPMailer or SwiftMailer
        
        $socket = fsockopen(LMS_SMTP_HOST, LMS_SMTP_PORT, $errno, $errstr, 30);
        
        if (!$socket) {
            throw new Exception("Cannot connect to SMTP server: $errstr ($errno)");
        }
        
        // Read initial response
        fgets($socket, 512);
        
        // SMTP commands
        $commands = [
            "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n",
            "STARTTLS\r\n",
            "AUTH LOGIN\r\n",
            base64_encode(LMS_SMTP_USERNAME) . "\r\n",
            base64_encode(LMS_SMTP_PASSWORD) . "\r\n",
            "MAIL FROM: <" . LMS_EMAIL_FROM . ">\r\n",
            "RCPT TO: <$to>\r\n",
            "DATA\r\n"
        ];
        
        foreach ($commands as $command) {
            fputs($socket, $command);
            $response = fgets($socket, 512);
            
            // Check for errors (basic check)
            if (strpos($response, '5') === 0) {
                fclose($socket);
                throw new Exception("SMTP Error: $response");
            }
        }
        
        // Send email content
        $email_content = "Subject: $subject\r\n";
        $email_content .= "From: " . LMS_EMAIL_FROM_NAME . " <" . LMS_EMAIL_FROM . ">\r\n";
        $email_content .= "To: $to\r\n";
        $email_content .= "MIME-Version: 1.0\r\n";
        $email_content .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $email_content .= $message . "\r\n.\r\n";
        
        fputs($socket, $email_content);
        $response = fgets($socket, 512);
        
        // Quit
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        $success = strpos($response, '250') === 0;
        
        if ($success) {
            $this->logger->info("Email sent via SMTP", [
                'to' => $to,
                'subject' => $subject,
                'smtp_host' => LMS_SMTP_HOST
            ]);
        } else {
            $this->logger->error("SMTP sending failed", [
                'to' => $to,
                'subject' => $subject,
                'response' => $response
            ]);
        }
        
        return $success;
    }
    
    /**
     * Send license expiration notification
     */
    public function sendLicenseExpirationNotice($license) {
        $subject = "License Expiration Notice - " . $license['product_name'];
        
        $message = $this->getEmailTemplate('license_expiration', [
            'customer_name' => $license['customer_name'],
            'product_name' => $license['product_name'],
            'license_key' => $license['license_key'],
            'expires_at' => $license['expires_at'],
            'days_remaining' => ceil((strtotime($license['expires_at']) - time()) / 86400)
        ]);
        
        return $this->sendEmail($license['customer_email'], $subject, $message);
    }
    
    /**
     * Send license activation notification
     */
    public function sendLicenseActivationNotice($license, $domain) {
        $subject = "License Activated - " . $license['product_name'];
        
        $message = $this->getEmailTemplate('license_activation', [
            'customer_name' => $license['customer_name'],
            'product_name' => $license['product_name'],
            'license_key' => $license['license_key'],
            'domain' => $domain,
            'activated_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->sendEmail($license['customer_email'], $subject, $message);
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($template, $variables) {
        $templates = [
            'license_expiration' => '
                <h2>License Expiration Notice</h2>
                <p>Dear {customer_name},</p>
                <p>Your license for <strong>{product_name}</strong> is expiring soon.</p>
                <p><strong>License Details:</strong></p>
                <ul>
                    <li>License Key: <code>{license_key}</code></li>
                    <li>Product: {product_name}</li>
                    <li>Expires: {expires_at}</li>
                    <li>Days Remaining: {days_remaining}</li>
                </ul>
                <p>Please renew your license to continue using the product.</p>
                <p>Best regards,<br>License Management Team</p>
            ',
            'license_activation' => '
                <h2>License Activation Confirmation</h2>
                <p>Dear {customer_name},</p>
                <p>Your license for <strong>{product_name}</strong> has been successfully activated.</p>
                <p><strong>Activation Details:</strong></p>
                <ul>
                    <li>License Key: <code>{license_key}</code></li>
                    <li>Product: {product_name}</li>
                    <li>Domain: {domain}</li>
                    <li>Activated: {activated_at}</li>
                </ul>
                <p>Your license is now active and ready to use.</p>
                <p>Best regards,<br>License Management Team</p>
            '
        ];
        
        $template_content = $templates[$template] ?? '<p>Email template not found.</p>';
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $template_content = str_replace('{' . $key . '}', htmlspecialchars($value), $template_content);
        }
        
        // Wrap in HTML layout
        return $this->wrapEmailTemplate($template_content);
    }
    
    /**
     * Wrap email content in HTML layout
     */
    private function wrapEmailTemplate($content) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>License Management System</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                h2 { color: #2c3e50; }
                code { background: #f1f1f1; padding: 2px 4px; border-radius: 3px; }
                ul { padding-left: 20px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                ' . $content . '
                <div class="footer">
                    <p>This is an automated message from ' . LMS_NAME . ' v' . LMS_VERSION . '</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfiguration($test_email) {
        $subject = "License Management System - Email Test";
        $message = $this->getEmailTemplate('test_email', [
            'test_date' => date('Y-m-d H:i:s'),
            'system_version' => LMS_VERSION,
            'smtp_configured' => LMS_SMTP_HOST ? 'Yes' : 'No'
        ]);
        
        // Add test template
        $test_content = '
            <h2>Email Configuration Test</h2>
            <p>This is a test email to verify your email configuration.</p>
            <p><strong>Test Details:</strong></p>
            <ul>
                <li>Test Date: {test_date}</li>
                <li>System Version: {system_version}</li>
                <li>SMTP Configured: {smtp_configured}</li>
            </ul>
            <p>If you received this email, your configuration is working correctly!</p>
        ';
        
        foreach (['test_date' => date('Y-m-d H:i:s'), 'system_version' => LMS_VERSION, 'smtp_configured' => LMS_SMTP_HOST ? 'Yes' : 'No'] as $key => $value) {
            $test_content = str_replace('{' . $key . '}', htmlspecialchars($value), $test_content);
        }
        
        $message = $this->wrapEmailTemplate($test_content);
        
        return $this->sendEmail($test_email, $subject, $message);
    }
}