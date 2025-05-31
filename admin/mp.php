<?php
session_start();
require_once '../ind/db.php';
$config = require_once '../includes/config.php';
$conn = getDbConnection();

// Idioma
if (!isset($_SESSION['lang'])) $_SESSION['lang'] = 'pt';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: mp.php");
    exit;
}
$lang = include "../lang/{$_SESSION['lang']}.php";

// Proteção de login e acesso
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}







$login = $_SESSION['username'] ?? '';

$saldo = 0; // valor padrão se não encontrado

if ($login) {
    // Consulta para buscar o saldo do usuário
    $stmt = $conn->prepare("SELECT balance FROM account_balance WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->bind_result($balanceFromDb);
    
    if ($stmt->fetch()) {
        $saldo = floatval($balanceFromDb);
    }

    $stmt->close();
}

$stmt = $conn->prepare("SELECT accesslevel FROM accounts WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) exit;
$user = $res->fetch_assoc();
$accessLevel = (int) $user['accesslevel'];
if (!in_array($accessLevel, [1, 2])) exit;

// Ações: entregar ou cancelar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) 
{
    $id = (int) $_POST['id'];
	
  if ($_POST['action'] === 'mark_processed') {
    // Buscar dados do pagamento
    $stmt = $conn->prepare("SELECT payment_id, char_name, processed FROM pix_payments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($paymentId, $charName, $alreadyProcessed);
    $stmt->fetch();
    $stmt->close();

    if ($alreadyProcessed) {
        header("Location: mp.php?msg=already_processed");
        exit;
    }

    // Consultar MercadoPago
    $url = "https://api.mercadopago.com/v1/payments/" . urlencode($paymentId);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $config['mercadopago_private_key'],
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $amount = $data['transaction_amount'] ?? 0.00;

        // Buscar login pelo nome do personagem
        $stmt = $conn->prepare("SELECT account_name FROM characters WHERE char_name = ?");
        $stmt->bind_param("s", $charName);
        $stmt->execute();
        $stmt->bind_result($accountName);
        $stmt->fetch();
        $stmt->close();

        if ($accountName) {
            $saldo = $amount;

            if ($config['use_bonus'] && $amount >= $config['bonus_threshold']) {
                $bonus = $amount * ($config['bonus_percent'] / 100);
                $saldo += $bonus;
            }

            // Entregar saldo
            $stmt = $conn->prepare("INSERT INTO account_balance (login, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)");
            $stmt->bind_param("sd", $accountName, $saldo);
            $stmt->execute();
            $stmt->close();

            // Marcar como entregue
            $stmt = $conn->prepare("UPDATE pix_payments SET processed = 1, status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

	
	
elseif ($_POST['action'] === 'cancel_payment') {
    // Buscar payment_id pelo ID
    $stmt = $conn->prepare("SELECT payment_id, status FROM pix_payments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($paymentId, $status);
    $stmt->fetch();
    $stmt->close();

    if (!$paymentId || $status !== 'pending') {
        // Ignorar se não encontrado ou não estiver pendente
        header("Location: mp.php?msg=invalid_status");
        exit;
    }

    // Cancelar via API do MercadoPago
    $url = "https://api.mercadopago.com/v1/payments/" . urlencode($paymentId);
    $data = ['status' => 'cancelled'];
    $headers = [
        "Authorization: Bearer " . $config['mercadopago_private_key'],
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        // Atualiza status no banco
        $stmt = $conn->prepare("UPDATE pix_payments SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: mp.php?msg=cancel_success");
        exit;
    } else {
        header("Location: mp.php?msg=cancel_failed");
        exit;
    }
}

    header("Location: mp.php?filtro=" . urlencode($_GET['filtro'] ?? '') . "&page=" . ($_GET['page'] ?? 1));
    exit;
}

// Filtro e paginação
$filtro = $_GET['filtro'] ?? '';
$porPagina = 7;
$paginaAtual = max(1, (int)($_GET['page'] ?? 1));
$offset = ($paginaAtual - 1) * $porPagina;

$searchSQL = $filtro ? "WHERE char_name LIKE ?" : '';
$countQuery = "SELECT COUNT(*) as total FROM pix_payments $searchSQL";
$countStmt = $conn->prepare($countQuery);
if ($filtro) {
    $likeFiltro = "%$filtro%";
    $countStmt->bind_param("s", $likeFiltro);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPaginas = ceil($total / $porPagina);

// Buscar dados
$dataQuery = "SELECT * FROM pix_payments $searchSQL ORDER BY id DESC LIMIT ? OFFSET ?";
if ($filtro) {
    $stmt = $conn->prepare($dataQuery);
    $stmt->bind_param("sii", $likeFiltro, $porPagina, $offset);
} else {
    $dataQuery = str_replace('WHERE ', '', $dataQuery);
    $stmt = $conn->prepare($dataQuery);
    $stmt->bind_param("ii", $porPagina, $offset);
}
$stmt->execute();
$donations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
        input, select { padding: 8px; background: #333; color: #fff; border: 1px solid #555; border-radius: 5px; }
        button { background: #ffd700; color: #000; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.95em; }
        th, td { padding: 8px; border: 1px solid #444; text-align: center; }
        th { background-color: #2c2c2c; }
        .pagination a { margin: 0 4px; color: #ffd700; text-decoration: none; }
    </style>

</head>
<body>
	
<script>

document.getElementById('filtro').addEventListener('input', function () {
  const termo = this.value.toLowerCase();
  const linhas = document.querySelectorAll('#tabelaContas tbody tr');

  linhas.forEach(function (linha) {
    const nomePersonagem = linha.children[1]?.textContent.toLowerCase(); // segunda <td>
    if (nomePersonagem && nomePersonagem.includes(termo)) {
      linha.style.display = '';
    } else {
      linha.style.display = 'none';
    }
  });
});


</script>
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

    <h2 style="margin-top: 20px;">Pagamentos PIX</h2>


<form method="GET" style="margin: 10px 0;">
  <input
    type="text"
    name="filtro"
    id="filtro"
    placeholder="🔍 Filtrar por personagem"
    style="margin-bottom: 15px; padding: 10px; width: 100%; max-width: 400px; border: 1px solid #555; background: #222; color: #fff; border-radius: 8px;"
    onfocus="this.style.borderColor='#f5c261'; this.style.boxShadow='0 0 5px #f5c261';"
    onblur="this.style.borderColor='#444'; this.style.boxShadow='none';"
  />
</form>





    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Personagem</th>
                <th>Payment ID</th>
                <th>Status</th>
                <th>Entregue</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($donations): ?>
            <?php foreach ($donations as $d): ?>
                <tr>
                    <td><?= $d['id'] ?></td>
                    <td><?= htmlspecialchars($d['char_name']) ?></td>
                    <td><?= htmlspecialchars($d['payment_id']) ?></td>
                    <td style="color: <?= $d['status'] === 'approved' ? '#0f0' : ($d['status'] === 'pending' ? '#ffa500' : '#f00') ?> ">
                        <?= ucfirst($d['status']) ?>
                    </td>
                    <td><?= $d['processed'] ? 'Sim' : 'Não' ?></td>
                    <td><?= $d['created_at'] ?></td>
                    <td>
                        <?php if (!$d['processed'] && $d['status'] === 'pending'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <button name="action" value="mark_processed" title="Entregar"><i class="fa fa-check"></i></button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <button name="action" value="cancel_payment" title="Cancelar"><i class="fa fa-times"></i></button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">Nenhum pagamento encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination" style="text-align: center; margin-top: 15px;">
        <?php if ($paginaAtual > 1): ?>
            <a href="?filtro=<?= urlencode($filtro) ?>&page=<?= $paginaAtual - 1 ?>">&laquo; Anterior</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="?filtro=<?= urlencode($filtro) ?>&page=<?= $i ?>" style="<?= $i === $paginaAtual ? 'font-weight:bold; text-decoration:underline;' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        <?php if ($paginaAtual < $totalPaginas): ?>
            <a href="?filtro=<?= urlencode($filtro) ?>&page=<?= $paginaAtual + 1 ?>">Próxima &raquo;</a>
        <?php endif; ?>
    </div>
</main>

    </div>


</body>
</html>
