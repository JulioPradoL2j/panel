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

// Verificação de accesslevel (1 = Admin, 2 = GM)
$login = $_SESSION['username'] ?? '';
$stmt = $conn->prepare("SELECT accesslevel FROM accounts WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit;
}

$user = $result->fetch_assoc();
if (!in_array($user['accesslevel'], [1, 2])) {
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
$accessLevel = (int) $user['accesslevel'];

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
                <div class="admin-user"><i class="fa fa-user-friends"></i> <?= $user['accesslevel'] == 1 ? 'Admin' : 'GM' ?></div>
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
  


  <h2><?= $lang['gerenciar_accounts'] ?? 'Gerenciar Saldo de Contas' ?></h2>
   	<form method="GET" style="margin-bottom: 20px;">
<input
  type="text"
  id="filtroLogin"
  name="buscar"
  placeholder="🔍 Buscar login..."
  value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>"
  style="margin-bottom: 15px; padding: 10px; width: 100%; max-width: 400px; border: 1px solid #555; background: #222; color: #fff; border-radius: 8px;"
  onfocus="this.style.borderColor='#f5c261'; this.style.boxShadow='0 0 5px #f5c261';"
  onblur="this.style.borderColor='#444'; this.style.boxShadow='none';"
/>

</form>
  <?php
    // Paginação
    $porPagina = 6;
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $offset = ($pagina - 1) * $porPagina;

    // Total de contas
    $totalStmt = $conn->query("SELECT COUNT(*) as total FROM account_balance");
    $total = $totalStmt->fetch_assoc()['total'];
    $totalPaginas = ceil($total / $porPagina);

    // Buscar contas para esta página
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $buscar = '%' . $_GET['buscar'] . '%';

    // Contar total filtrado
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM account_balance WHERE login LIKE ?");
    $stmt->bind_param("s", $buscar);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $totalPaginas = ceil($total / $porPagina);

    // Buscar logins filtrados com paginação
    $stmt = $conn->prepare("SELECT login, balance FROM account_balance WHERE login LIKE ? ORDER BY login ASC LIMIT ?, ?");
    $stmt->bind_param("sii", $buscar, $offset, $porPagina);
} else {
    $stmt = $conn->prepare("SELECT login, balance FROM account_balance ORDER BY login ASC LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $porPagina);

    // Total de contas
    $totalStmt = $conn->query("SELECT COUNT(*) as total FROM account_balance");
    $total = $totalStmt->fetch_assoc()['total'];
    $totalPaginas = ceil($total / $porPagina);
}

$stmt->execute();
$result = $stmt->get_result();


    // Atualizar saldo existente
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
        $editLogin = trim($_POST['edit_login']);
        $editValor = floatval($_POST['edit_valor']);
        $stmt = $conn->prepare("UPDATE account_balance SET balance = ? WHERE login = ?");
        $stmt->bind_param("ds", $editValor, $editLogin);
        $stmt->execute();
        header("Location: accounts.php?pagina=$pagina");
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
			
            header("Location: accounts.php?pagina=$pagina");
            exit;
        }
    }
	
  ?>

  <table id="tabelaContas" style="width:100%; margin-bottom: 20px; background-color: #1d1d1d; border-collapse: collapse; border-radius: 6px; overflow: hidden;">

  


    <thead>
      <tr style="background-color: #2c2c2c; color: #f5c261;">
        <th style="padding: 10px;">Login</th>
        <th style="padding: 10px;">Saldo (R$)</th>
        <th style="padding: 10px;">Novo Saldo</th>
        <th style="padding: 10px;">Ação</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr style="text-align: center; border-bottom: 1px solid #333;">
          <form method="POST">
            <td style="padding: 10px; color: white;"><?= htmlspecialchars($row['login']) ?></td>
            <td style="padding: 10px; color: white;"><?= number_format($row['balance'], 2, ',', '.') ?></td>
            <td style="padding: 10px;">
              <input type="number" step="0.01" name="edit_valor" value="<?= $row['balance'] ?>" required style="width: 100px; padding: 4px;" />
              <input type="hidden" name="edit_login" value="<?= htmlspecialchars($row['login']) ?>" />
            </td>
            <td style="padding: 10px;">
              <button type="submit" name="update_balance" style="background-color: #f5c261; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">Atualizar</button>
            </td>
          </form>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Navegação de páginas -->
  <div style="text-align:center; margin-bottom: 30px;">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
      <a href="?pagina=<?= $i ?>" style="margin: 0 5px; color: <?= $i == $pagina ? '#f5c261' : '#ccc' ?>; font-weight: bold;"><?= $i ?></a>
    <?php endfor; ?>
  </div>


<?php else: ?>
  <p style="color: red;">Apenas administradores podem gerenciar saldos.</p>
<?php endif; ?>


</main>


    </div>
<script>
document.getElementById('filtroLogin').addEventListener('input', function() {
  const termo = this.value.toLowerCase();
  const linhas = document.querySelectorAll('#tabelaContas tbody tr');

  linhas.forEach(function(linha) {
    const login = linha.querySelector('td').textContent.toLowerCase();
    if (login.includes(termo)) {
      linha.style.display = '';
    } else {
      linha.style.display = 'none';
    }
  });
});
</script>


</body>
</html>
