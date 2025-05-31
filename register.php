<?php
session_start();
require_once 'ind/db.php';
require_once 'includes/captcha.php';

$error = '';
$success = '';
$xmlContent = '';
$captcha = new Captcha();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');
    $captchaInput = trim($_POST['captcha'] ?? '');

    if (!$captcha->validateCaptcha($captchaInput)) {
        $error = "Código de verificação inválido ou expirado.";
    } elseif (strlen($username) < 4 || strlen($password) < 4) {
        $error = "Usuário e senha devem ter pelo menos 4 caracteres.";
    } elseif ($password !== $confirm) {
        $error = "As senhas não coincidem.";
    } else {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT login FROM accounts WHERE login = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Este nome de usuário já está em uso.";
        } else {
            $encodedPass = base64_encode(sha1($password, true));
            $stmt = $conn->prepare("INSERT INTO accounts (login, password, accesslevel) VALUES (?, ?, 0)");
            $stmt->bind_param("ss", $username, $encodedPass);
            if ($stmt->execute()) {
				
				$stmtBalance = $conn->prepare("INSERT INTO account_balance (login, balance) VALUES (?, 0.00)");
				$stmtBalance->bind_param("s", $username);
				$stmtBalance->execute();
	
                $success = "Conta registrada com sucesso! Redirecionando para o login...";
                
                // Geração do XML
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;

                $account = $xml->createElement('account');
                $xml->appendChild($account);

                $loginEl = $xml->createElement('login', htmlspecialchars($username));
                $account->appendChild($loginEl);

                $dateEl = $xml->createElement('registered_at', date('Y-m-d H:i:s'));
                $account->appendChild($dateEl);

                $xmlContent = $xml->saveXML();
            } else {
                $error = "Erro ao registrar. Tente novamente.";
            }
        }
    }
}

$currentCaptcha = $captcha->getCurrentCaptcha();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registrar - UCP</title>
  <link rel="stylesheet" href="templates/dark/assets/auth.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="shortcut icon" href="icon/favicon.png">

  <style>
    .success {
      margin-top: 15px;
      font-size: 1rem;
      color: #73ff7c;
      font-weight: 600;
      text-align: center;
    }

    .xml-download-msg {
      margin-top: 10px;
      color: #e6c76b;
      font-size: 0.95rem;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <img src="templates/dark/assets/img-dark/logo.png" alt="Logo do Servidor" class="logo">
    <form method="POST" autocomplete="off" spellcheck="false">
      <h2>Register Account</h2>

      <div class="input-group">
        <i class="fas fa-user"></i>
        <input type="text" name="username" placeholder="Username" required />
      </div>

      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Password" required />
      </div>

      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="confirm" placeholder="Confirm Password" required />
      </div>

      <div class="captcha-display">
        <span><?= htmlspecialchars($currentCaptcha) ?></span>
      </div>

      <div class="input-group">
        <i class="fas fa-shield-alt"></i>
        <input type="text" name="captcha" placeholder="Enter the code below" required />
      </div>

      <button type="submit">Register</button>

      <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
      <?php elseif ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
        <p class="xml-download-msg">Redirecting in 15 seconds...</p>
		
        <script>
          // Redirecionar após 15 segundos
          setTimeout(() => {
            window.location.href = 'index.php';
          }, 15000);

          // Gerar e baixar XML automaticamente
          const xmlContent = <?= json_encode($xmlContent) ?>;
          const blob = new Blob([xmlContent], { type: "application/xml" });
          const link = document.createElement("a");
          link.href = URL.createObjectURL(blob);
          link.download = "conta_" + Date.now() + ".xml";
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        </script>
      <?php endif; ?>

      <p class="register-link">Already have an account? <a href="index.php">Login here</a></p>
    </form>
  </div>

  <script>
    document.querySelector('input[name="captcha"]').addEventListener('input', function() {
      this.value = this.value.toUpperCase();
    });
  </script>
</body>
</html>
