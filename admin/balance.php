<?php
session_start();
require_once '../ind/db.php';
$config = require_once '../includes/config.php';
$conn = getDbConnection();

// Idioma padrão
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'pt';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: index.php");
    exit;
}
$lang = include "../lang/{$_SESSION['lang']}.php";



// Proteção de login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

// Verificação de access_level (1 = Admin, 2 = GM)
$login = $_SESSION['username'] ?? '';
$stmt = $conn->prepare("SELECT access_level FROM accounts WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit;
}

$user = $result->fetch_assoc();
if (!in_array($user['access_level'], [1, 2])) {
    header("Location: ../index.php");
    exit;
}
// Dados de usuário e fatura (como você já tinha)
$username = $_SESSION['username'] ?? 'Usuário';


$saldo = 0; // valor padrão se não encontrado

if ($username) {
    // Consulta para buscar o saldo do usuário
    $stmt = $conn->prepare("SELECT balance FROM account_balance WHERE login = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($balanceFromDb);
    
    if ($stmt->fetch()) {
        $saldo = floatval($balanceFromDb);
    }

    $stmt->close();
}
$accessLevel = (int) $user['access_level'];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
	<link rel="shortcut icon" href="../icon/favicon.png">
	<link rel="stylesheet" href="admin.css" />
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
	
	<style>
.account-form form {
    margin-bottom: 15px;
    background-color: #292929;
    padding: 10px;
    border-radius: 6px;
}

.account-form h3 {
    color: #f5c261;
    margin-bottom: 10px;
}

.account-form input[type="number"] {
    margin-top: 4px;
}


.account-form label {
    display: block;
    margin-top: 10px;
    color: #f5c261;
}

.account-form input {
    width: 100%;
    padding: 8px;
    margin-top: 4px;
    border: none;
    border-radius: 4px;
    background: #2b2b2b;
    color: white;
}

.account-form button {
    margin-top: 15px;
    background-color: #f5c261;
    border: none;
    padding: 10px;
    color: #000;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.success {
    margin-top: 15px;
    color: #8bc34a;
}

.error {
    margin-top: 15px;
    color: #f44336;
}


    </style>
</head>
<body>
    <div class="admin-panel">
        <aside class="admin-sidebar">
            <div class="admin-header">
                <div class="admin-user"><i class="fa fa-user-friends"></i> <?= $user['access_level'] == 1 ? 'Admin' : 'GM' ?></div>
				 <span class="admin-balance"><?= $lang['saldo'] ?> R$ <?= number_format($saldo, 2, ',', '.') ?></span>
           
            </div>
		<nav class="admin-nav">
<ul>
    <li><a href="../"><i class="fa fa-home"></i> <span>Home</span></a></li>
    <li><a href="index.php"><i class="fa fa-box"></i> <span>Itens Shop</span></a></li>
    <li><a href="gerenciar.php"><i class="fa fa-tools"></i> <span>Gerenciar Shop</span></a></li>
    <li><a href="accounts.php"><i class="fa fa-users"></i> <span>Gerenciar Accounts</span></a></li>
    <li><a href="balance.php"><i class="fa fa-wallet"></i> <span>Gerenciar Saldo</span></a></li>
    <li><a href="admins.php"><i class="fa fa-user-shield"></i> <span>Gerenciar GMs</span></a></li>
    <li><a href="mp.php"><i class="fa fa-qrcode"></i> <span>Doação Pix</span></a></li>
    <li><a href="stripe.php"><i class="fa fa-credit-card"></i> <span>Doação Stripe</span></a></li>
</ul>
		</nav>

        </aside>
 <main class="admin-content">
  <header class="header">
    <img src="../templates/dark/assets/img-dark/logo-db.png" alt="Logo" class="logo-db" />
    <div class="lang-icons">
      <a href="?lang=pt"><img src="../icon/pt.png" alt="Português" /></a>
      <a href="?lang=en"><img src="../icon/us.png" alt="English" /></a>
    </div>
  </header>

<?php if ($accessLevel === 1): ?>
<!-- Adicionar saldo (somente para Admin) -->
<form method="POST" class="account-form">
    <h3>Adicionar saldo a qualquer conta</h3>
    <label for="login"><?= $lang['login'] ?? 'Login da Conta' ?>:</label>
    <input type="text" name="login" required placeholder="Ex: jogador123" />
    
    <label for="valor"><?= $lang['valor'] ?? 'Valor a Adicionar' ?> (R$):</label>
    <input type="number" step="0.01" name="valor" required placeholder="Ex: 10.00" />
    
    <button type="submit" name="add_balance"><?= $lang['adicionar'] ?? 'Adicionar Saldo' ?></button>
</form>
<?php else: ?>
  <p style="color: red;">Apenas administradores podem gerenciar saldos.</p>
<?php endif; ?>

  
  


  <?php
   

    // Atualizar saldo existente
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
        $editLogin = trim($_POST['edit_login']);
        $editValor = floatval($_POST['edit_valor']);
        $stmt = $conn->prepare("UPDATE account_balance SET balance = ? WHERE login = ?");
        $stmt->bind_param("ds", $editValor, $editLogin);
        $stmt->execute();
        header("Location: balance.php");
        exit;
    }

    // Adicionar saldo
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
        $loginInput = trim($_POST['login']);
        $valorInput = floatval($_POST['valor']);
        if ($valorInput <= 0) {
            echo "<p class='error'>Valor inválido.</p>";
        } else {
            $stmt = $conn->prepare("SELECT balance FROM account_balance WHERE login = ?");
            $stmt->bind_param("s", $loginInput);
            $stmt->execute();
            $stmt->bind_result($existingBalance);
            if ($stmt->fetch()) {
                $stmt->close();
                $newBalance = $existingBalance + $valorInput;
                $update = $conn->prepare("UPDATE account_balance SET balance = ? WHERE login = ?");
                $update->bind_param("ds", $newBalance, $loginInput);
                $update->execute();
            } else {
                $stmt->close();
                $insert = $conn->prepare("INSERT INTO account_balance (login, balance) VALUES (?, ?)");
                $insert->bind_param("sd", $loginInput, $valorInput);
                $insert->execute();
            }
			
            header("Location: balance.php");
            exit;
        }
    }
	
  ?>
</main>


    </div>

<script>

</script>

</body>
</html>
