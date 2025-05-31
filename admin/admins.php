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
    header("Location: gerenciar.php");
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

// Alteração de nível de acesso
if ($accessLevel == 1 && isset($_POST['alterarNivel'], $_POST['login'], $_POST['accesslevel'])) {
    $login = $_POST['login'];
    $novoNivel = (int)$_POST['accesslevel'];

    $stmt = $conn->prepare("UPDATE accounts SET accesslevel = ? WHERE login = ?");
    $stmt->bind_param("is", $novoNivel, $login);
    $stmt->execute();
    $stmt->close();

    header("Location: admins.php?filtro=" . urlencode($filtro) . "&pagina=" . $paginaAtual);
    exit;
}


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
   
/* Tabela */
table {
    width: 100%;
    border-collapse: collapse;
    background-color: #222;
    border-radius: 8px;
    overflow: hidden;
}

thead tr {
    background-color: #333;
    color: #f0c040;
}

thead th, tbody td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #2d2d2d;
}

tbody tr:hover {
    background-color: #2a2a2a;
}

select, input[type="text"], button {
    background-color: #1e1e1e;
    color: #fff;
    border: 1px solid #444;
    border-radius: 6px;
    padding: 6px 10px;
    font-family: 'Orbitron', sans-serif;
}

select:focus, input:focus {
    outline: none;
    border-color: #f0c040;
}

button {
    cursor: pointer;
    background-color: #f0c040;
    color: #000;
    font-weight: bold;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #d6aa30;
}

/* Paginação */
.pagination a {
    margin: 0 5px;
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 4px;
    background-color: #333;
}

.pagination a:hover, .pagination a:focus {
    background-color: #f0c040;
    color: #000;
}
  .account-form h3 {
    color: #f5c261;
    margin-bottom: 10px;
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
		  
		  <?php
// Definir quantos por página
$porPagina = 7;
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($paginaAtual - 1) * $porPagina;

// Filtro de busca
$filtro = $_GET['filtro'] ?? '';
$filtroSQL = "%$filtro%";

// Contagem total para paginação
$stmt = $conn->prepare("SELECT COUNT(*) FROM accounts WHERE login LIKE ?");
$stmt->bind_param("s", $filtroSQL);
$stmt->execute();
$stmt->bind_result($totalRegistros);
$stmt->fetch();
$stmt->close();

$totalPaginas = ceil($totalRegistros / $porPagina);

// Buscar os dados paginados
$stmt = $conn->prepare("SELECT login, lastactive, accesslevel FROM accounts WHERE login LIKE ? ORDER BY login ASC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $filtroSQL, $porPagina, $offset);
$stmt->execute();
$result = $stmt->get_result();
$contas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div style="margin-bottom: 15px;">
    <input type="text" id="filtro" placeholder="🔍 Buscar login..." style="margin-bottom: 15px; padding: 10px; width: 100%; max-width: 400px; border: 1px solid #555; background: #222; color: #fff; border-radius: 8px;" value="<?= htmlspecialchars($filtro) ?>">
</div>

<table style="width: 100%; border-collapse: collapse; color: #fff;">
    <thead>
        <tr style="background: #333;">
            <th style="padding: 10px;">Login</th>
            <th>Last Active</th>
            <th>Acesso</th>
            <?php if ($accessLevel == 1): ?><th>Ação</th><?php endif; ?>
        </tr>
    </thead>
    <tbody id="tabela-contas">
        <?php foreach ($contas as $conta): ?>
            <tr>
                <td style="padding: 10px;"><?= htmlspecialchars($conta['login']) ?></td>
                <td>
					<?php
						$timestamp = (int)($conta['lastactive']);
						echo date('H:i d/m/Y', $timestamp);
					?>
				</td>

                <td>
                    <?php
                        switch ($conta['accesslevel']) {
                            case 1: echo 'Admin'; break;
                            case 2: echo 'GM'; break;
                            default: echo 'Jogador'; break;
                        }
                    ?>
                </td>
                <?php if ($accessLevel == 1): ?>
                <td>
                    <form method="post" action="admins.php" style="display:inline;">
                        <input type="hidden" name="login" value="<?= $conta['login'] ?>">
                        <select name="accesslevel">
                            <option value="0" <?= $conta['accesslevel'] == 0 ? 'selected' : '' ?>>Jogador</option>
                            <option value="1" <?= $conta['accesslevel'] == 1 ? 'selected' : '' ?>>Admin</option>
                            <option value="2" <?= $conta['accesslevel'] == 2 ? 'selected' : '' ?>>GM</option>
                        </select>
                        <button type="submit" name="alterarNivel" style="background-color: #f5c261; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">Salvar</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Paginação -->
<div style="margin-top: 20px; text-align: center;">
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <a href="?pagina=<?= $i ?>&filtro=<?= urlencode($filtro) ?>" style="margin: 0 5px; color: #<?= $i == $paginaAtual ? 'ff0' : 'fff' ?>;"><?= $i ?></a>
    <?php endfor; ?>
</div>





	
        </main>
    </div>
	
<script>
// Filtro dinâmico
document.getElementById('filtro').addEventListener('input', function () {
    const valor = this.value;
    const url = new URL(window.location.href);
    url.searchParams.set('filtro', valor);
    url.searchParams.set('pagina', 1);
    window.location.href = url.toString();
});
</script>
</body>
</html>
