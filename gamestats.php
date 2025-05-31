<?php
session_start();
require_once 'ind/db.php';
$conn = getDbConnection();

// Idioma padrão
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'pt';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: gamestats.php");
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
// Buscar top jogadores ordenados por PvP kills, limit 7
function buscarTopJogadores($conn) {
    $query = "SELECT char_name, pvpkills, pkkills, online, onlinetime FROM characters ORDER BY pvpkills DESC LIMIT 7";
    return $conn->query($query);
}

$topJogadores = buscarTopJogadores($conn);


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



.stats-table {
  width: 100%;
  border-collapse: collapse;
  background-color: #1b110e;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 0 8px rgba(240,165,0,0.3);
}

/* Cabeçalho da tabela */
.stats-table thead tr {
  background: linear-gradient(90deg, #f0a500, #d97706);
  color: #1a1a1a;
  font-weight: 700;
  text-transform: uppercase;
}

/* Células do cabeçalho */
.stats-table thead th {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 2px solid #d97706;
}

/* Linhas da tabela */
.stats-table tbody tr {
  border-bottom: 1px solid #1b110e;
  transition: background-color 0.3s ease;
}

/* Alternar cor das linhas */
.stats-table tbody tr:nth-child(even) {
  background-color: #292940;
}

/* Efeito hover nas linhas */
.stats-table tbody tr:hover {
  background-color: #44445a;
  cursor: default;
}

/* Células da tabela */
.stats-table tbody td {
  padding: 12px 15px;
  color: #eee;
}

/* Status online com cor */
.stats-table tbody td:nth-child(4) {
  font-weight: 700;
}

/* Status online e offline com cores distintas */
.stats-table tbody td:nth-child(4):contains('Online') {
  color: #4caf50; /* verde */
}
.stats-table tbody td:nth-child(4):contains('Offline') {
  color: #f44336; /* vermelho */
}


@media (max-width: 768px) {
  .stats-table thead {
    display: none;
  }
  .stats-table, 
  .stats-table tbody, 
  .stats-table tr, 
  .stats-table td {
    display: block;
    width: 100%;
  }
  .stats-table tr {
    margin-bottom: 15px;
    border-bottom: 2px solid #f0a500;
  }
  .stats-table td {
    padding-left: 50%;
    position: relative;
    text-align: left;
  }
  .stats-table td::before {
    position: absolute;
    left: 15px;
    width: 45%;
    padding-right: 10px;
    white-space: nowrap;
    font-weight: 700;
    color: #f0a500;
  }
  .stats-table td:nth-of-type(1)::before { content: "<?= $lang['char_name'] ?>"; }
  .stats-table td:nth-of-type(2)::before { content: "<?= $lang['pvp_kills'] ?>"; }
  .stats-table td:nth-of-type(3)::before { content: "<?= $lang['pk_kills'] ?>"; }
  .stats-table td:nth-of-type(4)::before { content: "<?= $lang['status'] ?>"; }
  .stats-table td:nth-of-type(5)::before { content: "<?= $lang['online_time'] ?>"; }
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
            <h2><?= $lang['stats_top_players'] ?? 'Top Players' ?></h2>
            <table class="stats-table" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th><?= $lang['char_name'] ?? 'Name' ?></th>
                        <th><?= $lang['pvp_kills'] ?? 'PvP Kills' ?></th>
                        <th><?= $lang['pk_kills'] ?? 'PK Kills' ?></th>
                        <th><?= $lang['status'] ?? 'Status' ?></th>
                        <th><?= $lang['online_time'] ?? 'Online Time' ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($topJogadores && $topJogadores->num_rows > 0): ?>
                    <?php while ($row = $topJogadores->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['char_name']) ?></td>
                        <td><?= (int)$row['pvpkills'] ?></td>
                        <td><?= (int)$row['pkkills'] ?></td>
                        <td>
                            <?php
                            echo $row['online'] ? ($lang['online'] ?? 'Online') : ($lang['offline'] ?? 'Offline');
                            ?>
                        </td>
                        <td>
                            <?php
                            // onlinetime geralmente em segundos, converter para hh:mm:ss
                            $seconds = (int)$row['onlinetime'];
                            $hours = floor($seconds / 3600);
                            $minutes = floor(($seconds % 3600) / 60);
                            $secs = $seconds % 60;
                            echo sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5"><?= $lang['no_players_found'] ?? 'Nenhum jogador encontrado.' ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html>
