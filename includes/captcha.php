<?php
class Captcha {
    private $session;
    private $ip;
    private $expirationTime = 120; // 60 segundos

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->session = &$_SESSION;
    }

    public function generateCaptcha() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $captchaString = '';
        
        // Gera 6 caracteres aleatórios
        for ($i = 0; $i < 6; $i++) {
            $captchaString .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Armazena o captcha e o timestamp
        $this->session['captcha_' . $this->ip] = [
            'code' => $captchaString,
            'timestamp' => time()
        ];

        return $captchaString;
    }

    public function validateCaptcha($userInput) {
        if (empty($userInput)) {
            return false;
        }

        if (!isset($this->session['captcha_' . $this->ip])) {
            return false;
        }

        $captchaData = $this->session['captcha_' . $this->ip];
        
        // Verifica se o captcha expirou
        if (time() - $captchaData['timestamp'] > $this->expirationTime) {
            unset($this->session['captcha_' . $this->ip]);
            return false;
        }

        // Verifica se o código está correto
        if (strtoupper(trim($userInput)) !== $captchaData['code']) {
            return false;
        }

        // Limpa o captcha após validação bem-sucedida
        unset($this->session['captcha_' . $this->ip]);
        return true;
    }

    public function canGenerateNewCaptcha() {
        if (!isset($this->session['captcha_' . $this->ip])) {
            return true;
        }

        $captchaData = $this->session['captcha_' . $this->ip];
        return (time() - $captchaData['timestamp'] > $this->expirationTime);
    }

    public function getCurrentCaptcha() {
        if (isset($this->session['captcha_' . $this->ip])) {
            return $this->session['captcha_' . $this->ip]['code'];
        }
        return $this->generateCaptcha();
    }
} 