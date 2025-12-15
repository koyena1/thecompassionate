<?php
namespace PHPMailer\PHPMailer;

class SMTP
{
    const LE = "\r\n";
    protected $socket;

    public function connect($host, $port)
    {
        // 1. Connect without SSL first
        $this->socket = @stream_socket_client(
            "tcp://$host:$port",
            $errno,
            $errstr,
            30
        );

        if (!$this->socket) return false;
        
        $this->get_lines(); // Read server greeting
        return true;
    }

    public function startTLS()
    {
        // 2. Send STARTTLS command
        fwrite($this->socket, "EHLO " . gethostname() . self::LE);
        $this->get_lines();

        fwrite($this->socket, "STARTTLS" . self::LE);
        $response = $this->get_lines();

        if (strpos($response, '220') === false) return false;

        // 3. Enable Crypto with LOOSE security settings (Fixes "Wrong Version" error)
        return stream_socket_enable_crypto(
            $this->socket, 
            true, 
            STREAM_CRYPTO_METHOD_TLS_CLIENT, // Auto-detect version
            $this->get_ssl_context()
        );
    }

    protected function get_ssl_context() {
        return stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
    }

    public function authenticate($username, $password)
    {
        // Send EHLO again after encryption starts
        fwrite($this->socket, "EHLO " . gethostname() . self::LE);
        $this->get_lines();

        fwrite($this->socket, "AUTH LOGIN" . self::LE);
        $this->get_lines();

        fwrite($this->socket, base64_encode($username) . self::LE);
        $this->get_lines();

        fwrite($this->socket, base64_encode($password) . self::LE);
        $response = $this->get_lines();

        // Check for "235 Authentication successful"
        if (strpos($response, '235') === false) return false;
        
        return true;
    }

    public function mail($from) {
        $this->client_send("MAIL FROM:<$from>");
    }

    public function recipient($to) {
        $this->client_send("RCPT TO:<$to>");
    }

    public function data($header, $body) {
        $this->client_send("DATA");
        $this->client_send($header . self::LE . self::LE . $body . self::LE . ".");
    }

    public function quit() {
        $this->client_send("QUIT");
        fclose($this->socket);
    }

    protected function client_send($data) {
        fwrite($this->socket, $data . self::LE);
        return $this->get_lines();
    }

    protected function get_lines() {
        $data = "";
        while ($str = fgets($this->socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $data;
    }
}
?>