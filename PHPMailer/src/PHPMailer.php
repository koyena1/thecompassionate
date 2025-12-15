<?php
namespace PHPMailer\PHPMailer;

class PHPMailer
{
    public $From = 'root@localhost';
    public $FromName = 'Safe Space';
    public $Subject = '';
    public $Body = '';
    public $Host = 'localhost';
    public $Port = 25;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $SMTPAuth = false;
    public $ErrorInfo = '';
    
    protected $to = [];

    public function isSMTP() { }
    public function isHTML($bool) { }

    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function addAddress($address, $name = '') {
        $this->to[] = [$address, $name];
    }

    public function send() {
        try {
            // Turn off PHP error reporting for this block to catch errors manually
            $old_error_reporting = error_reporting(0);
            
            $result = $this->smtpSend();
            
            // Restore error reporting
            error_reporting($old_error_reporting);
            
            return $result;
        } catch (\Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            throw $e;
        }
    }

    protected function smtpSend() {
        $smtp = new SMTP;
        $host = $this->Host;
        
        // 1. Connect
        if (!$smtp->connect($host, $this->Port)) {
            throw new Exception("Could not connect to SMTP host. Check internet or firewall.");
        }

        // 2. Start TLS (Security)
        if ($this->SMTPSecure == 'tls') {
            if (!$smtp->startTLS()) {
                throw new Exception("Encryption failed. Your XAMPP might need newer OpenSSL.");
            }
        }
        
        // 3. Authenticate
        if ($this->SMTPAuth) {
            if (!$smtp->authenticate($this->Username, $this->Password)) {
                throw new Exception("Authentication failed. Check your Gmail App Password.");
            }
        }
        
        // 4. Send Email
        $smtp->mail($this->From);
        foreach ($this->to as $to) {
            $smtp->recipient($to[0]);
        }

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $this->FromName . " <" . $this->From . ">\r\n";
        $headers .= "Subject: " . $this->Subject . "\r\n";
        
        $smtp->data($headers, $this->Body);
        $smtp->quit();
        
        return true;
    }
}
?>