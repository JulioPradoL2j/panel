<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/captcha.php';

$error = '';
$captcha = new Captcha();

// Se formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $captchaInput = $_POST['captcha'] ?? '';

    if (!$captcha->validateCaptcha($captchaInput)) {
        $error = "Código de verificação inválido ou expirado.";
    } elseif (loginUser($username, $password)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Usuário ou senha inválidos.";
    }
}
$currentCaptcha = $captcha->getCurrentCaptcha();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - UCP</title>
  <link rel="stylesheet" href="templates/dark/assets/auth.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="shortcut icon" href="icon/favicon.png">
</head>
<body>
  <div class="login-container">
    <img src="templates/dark/assets/img-dark/logo.png" alt="Logo do Servidor" class="logo">
    <form method="POST" autocomplete="off" spellcheck="false">
      <h2>User Panel</h2>
		<div class="input-group">
		<i class="fas fa-user"></i>
		<input type="text" name="username" placeholder="Username" required autofocus />
		</div>
		
		<div class="input-group">
		<i class="fas fa-lock"></i>
		<input type="password" name="password" placeholder="Password" required />
		</div>
		
		<div class="captcha-display">
			<span><?= htmlspecialchars($currentCaptcha) ?></span>
		</div>
		
		
		<div class="input-group">
			<i class="fas fa-shield-alt"></i>
			<input type="text" name="captcha" placeholder="Enter the code below" required />
		</div>
		


      <button type="submit">Login</button>
      <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
    </form>
  </div>
  <script>
  document.querySelector('input[name="captcha"]').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
  });
  </script>
</body>
</html>
