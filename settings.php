<?php
session_start();
require_once 'ind/db.php';
$config = require_once 'includes/config.php';
$conn = getDbConnection();



// Idioma padrão
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'pt';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: settings.php");
    exit;
}
$lang = include "lang/{$_SESSION['lang']}.php";

// Proteção de login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}



$saldo = 0; // valor padrão
$username = $_SESSION['username'] ?? '';

if ($username) {
    // Consulta do saldo
    $stmt = $conn->prepare("SELECT balance FROM account_balance WHERE login = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($balanceFromDb);
    
    if ($stmt->fetch()) {
        $saldo = floatval($balanceFromDb);
    }

    $stmt->close();

    // Consulta do access_level
    $stmt = $conn->prepare("SELECT access_level FROM accounts WHERE login = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $_SESSION['access_level'] = (int)$row['access_level'];
    }

    $stmt->close();
}
/**
 * Retorna os personagens do usuário logado.
 */
function buscarPersonagens($conn, $accountName) {
    $stmt = $conn->prepare("SELECT char_name, race FROM characters WHERE account_name = ? ORDER BY char_slot ASC LIMIT 7");
    $stmt->bind_param("s", $accountName);
    $stmt->execute();
    return $stmt->get_result();
}

$personagens = buscarPersonagens($conn, $username);

// Mapeamento de raças para imagens
$raceMap = ['Humano', 'Elfo', 'DarkElf', 'Orc', 'Drawn'];

?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
<script>
  window.USER_ACCESS_LEVEL = <?= isset($_SESSION['access_level']) ? (int)$_SESSION['access_level'] : 0 ?>;
</script>
    <meta charset="UTF-8">
    <title>Painel UCP - Lineage II</title>
    <link rel="stylesheet" href="templates/dark/assets/auth.css" />
    <link rel="stylesheet" href="templates/dark/assets/dark.css" />
    <link rel="shortcut icon" href="icon/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
	<script src="../admin_button.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<style>


.password-change-container {
  position: relative;
  z-index: 1;
  width: 420px;
  max-height: 100%;
  margin: 10px auto;
  background: rgba(24, 20, 20, 0.92);
  border-radius: 18px;
  padding: 40px 35px;
  box-shadow: 0 0 35px rgba(255, 191, 115, 0.15);
  text-align: center;
  animation: floatUpDown 10s ease-in-out infinite;
   overflow-y: auto;
}


.password-change-container h3 {
  font-family: 'Cinzel Decorative', serif;
  font-size: 14px;
  color: #eec889;
  margin-bottom: 25px;
}

.password-change-container .input-group {
  position: relative;
  margin: 10px 0;
}

.password-change-container .input-group i {
  position: absolute;
  top: 50%;
  left: 15px;
  transform: translateY(-50%);
  color: #aaa;
  font-size: 12px;
}

.password-change-container input {
  width: 85%;
  padding: 14px 18px 14px 45px;
  font-size: 14px;
  border: 1px solid #3a2e2a;
  border-radius: 10px;
  background: #191717;
  color: #eee;
  transition: all 0.3s ease;
}

.password-change-container input:focus {
  background: #26221f;
  border-color: #eec889;
  box-shadow: 0 0 10px rgba(255, 204, 102, 0.25);
  outline: none;
}

.password-change-container .captcha-display {
  font-family: monospace;
  font-size: 14px;
  letter-spacing: 3px;
  margin: 10px 0;
  background: #111;
  border: 1px dashed #777;
  padding: 12px;
  border-radius: 10px;
  color: #eec889;
  user-select: none;
  text-align: center;
}

.password-change-container button {
  margin-top: 1px;
  width: 100%;
  padding: 14px;
  font-size: 14px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(to right, #eac27a, #d89f4d);
  color: #111;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}
.password-change-container button:hover {
  background: linear-gradient(to right, #f4d9a6, #c48b3e);
  box-shadow: 0 0 12px #eac27a;
}

.password-change-container .success-msg,
.password-change-container .error-msg {
  font-size: 14px;
  padding: 12px;
  border-radius: 10px;
  font-weight: bold;
  margin-bottom: 20px;
  text-align: center;
  animation: fadeIn 1s ease;
}

.password-change-container .success-msg {
  background-color: #28a745;
  color: #fff;
}

.password-change-container .error-msg {
  background-color: #dc3545;
  color: #fff;
}

@keyframes floatUpDown {
  0%, 100% {
    transform: translateY(0) translateX(0);
  }
  50% {
    transform: translateY(10px) translateX(3px);
  }
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
</style>



</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="profile">
            <span class="user"><i class="fa fa-user"></i> <?= htmlspecialchars($username) ?></span>
            <span class="saldo"><?= $lang['saldo'] ?>: R$ <?= number_format($saldo, 2, ',', '.') ?></span>
            
        </div>
        <nav class="menu">
            <a href="index.php"><i class="fa fa-home"></i> <?= $lang['menu']['home'] ?></a>
            <a href="index.php"><i class="fa fa-user-friends"></i> <?= $lang['menu']['referral'] ?></a>
            <a href="donate.php"><i class="fa fa-donate"></i> <?= $lang['menu']['donations'] ?></a>
            <a href="services.php"><i class="fa fa-cogs"></i> <?= $lang['menu']['services'] ?></a>
            <a href="shop.php"><i class="fa fa-shopping-cart"></i> <?= $lang['menu']['shop'] ?></a>
            <a href="gamestats.php"><i class="fa fa-chart-bar"></i> <?= $lang['menu']['stats'] ?></a>
            <a href="settings.php"><i class="fa fa-wrench"></i> <?= $lang['menu']['settings'] ?></a>
            <a href="<?= $config['site_url'] ?>"><i class="fa fa-globe"></i> <?= $lang['menu']['site'] ?></a>
            <a href="logout.php"><i class="fa fa-sign-out-alt"></i> <?= $lang['menu']['logout'] ?></a>
        </nav>
    </aside>

    <main class="main-panel">
        <header class="header">
            <img src="templates/dark/assets/img-dark/logo-db.png" alt="Logo" class="logo-db" />
            <div class="lang-icons">
                <a href="?lang=pt"><img src="icon/pt.png" alt="Português" /></a>
                <a href="?lang=en"><img src="icon/us.png" alt="English" /></a>
            </div>
        </header>

        <section class="content">
           
			<?php
			require_once 'includes/captcha.php';
			$captcha = new Captcha();
			$captchaCode = $captcha->getCurrentCaptcha();
			?>
			
			<div class="password-change-container">
			 <h3><?= $lang['change_password'] ?? 'Trocar Senha' ?></h3>
			<?php if (isset($_SESSION['password_change_success'])): ?>
				<div class="success-msg"><?= $_SESSION['password_change_success'] ?></div>
				<?php unset($_SESSION['password_change_success']); ?>
			<?php elseif (isset($_SESSION['password_change_error'])): ?>
				<div class="error-msg"><?= $_SESSION['password_change_error'] ?></div>
				<?php unset($_SESSION['password_change_error']); ?>
			<?php endif; ?>
		
			<form method="post" action="change_password.php" class="password-form">
				<div class="input-group">
					<i class="fa fa-lock"></i>
					<input type="password" name="old_password" placeholder="<?= $lang['old_password'] ?>" required minlength="3" />
				</div>
		
				<div class="input-group">
					<i class="fa fa-lock"></i>
					<input type="password" name="new_password" placeholder="<?= $lang['new_password'] ?>" required minlength="8" />
				</div>
		
				<div class="input-group">
					<i class="fa fa-lock"></i>
					<input type="password" name="confirm_password" placeholder="<?= $lang['confirm_password'] ?>" required minlength="8" />
				</div>
		
				<label><?= $lang['captcha'] ?? 'Captcha' ?>:</label>
				<div class="captcha-display"><?= $captchaCode ?></div>
		
				<div class="input-group">
					<i class="fa fa-shield"></i>
					<input type="text" name="captcha" placeholder="<?= $lang['captcha'] ?>" maxlength="6" required />
				</div>
		
				<button type="submit"><?= $lang['submit'] ?? 'Alterar Senha' ?></button>
			</form>
		</div>


        </section>
    </main>
</div>

</body>
</html>
