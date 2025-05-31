<?php
session_start();

// Idioma padrão
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'pt';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: services.php");
    exit;
}
$lang = include "lang/{$_SESSION['lang']}.php";

// Proteção de login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

require_once 'ind/db.php';
$conn = getDbConnection();

// Dados de usuário e fatura (como você já tinha)
$username = $_SESSION['username'] ?? 'Usuário';

require_once 'ind/db.php';
$config = require_once 'includes/config.php';
$conn = getDbConnection();



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
.name-change-container {
  background: rgba(24, 20, 20, 0.92);
  border-radius: 18px;
  padding: 40px;
  margin: -10px auto;
  width: 100%;
  max-width: 550px;
  box-shadow: 0 0 5px rgba(255, 191, 115, 0.15);
  animation: fadeIn 1.5s ease;
  text-align: center;
}

.name-change-container h2 {
	margin: -20px auto;
  font-family: 'Cinzel Decorative', serif;
  font-size: 0.8rem;
  color: #eec889;
  margin-bottom: 25px;
}

.name-change-container select,
.name-change-container input {
  width: 85%;
  padding: 12px 15px;
  font-size: 0.8rem;
  margin-bottom: 15px;
  border-radius: 10px;
  border: 1px solid #3a2e2a;
  background: #191717;
  color: #eee;
}

.name-change-container input:focus {
  border-color: #eec889;
  background: #26221f;
  box-shadow: 0 0 8px rgba(255, 204, 102, 0.2);
  outline: none;
}

.name-change-container button {
  width: 50%;
  padding: 14px;
 font-size: 0.8rem;
  border-radius: 10px;
  border: none;
  background: linear-gradient(to right, #eac27a, #d89f4d);
  color: #111;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}

.name-change-container button:hover {
  background: linear-gradient(to right, #f4d9a6, #c48b3e);
  box-shadow: 0 0 12px #eac27a;
}


.msg-container {

  padding: 5px;
  margin: 20px auto;
  max-width: 450px;
  border-radius: 8px;
  font-weight: bold;
  text-align: center;
  font-size: 0.8rem;
}
.msg-container.sucesso {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}
.msg-container.erro {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
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
            <a href="#"><i class="fa fa-globe"></i> <?= $lang['menu']['site'] ?></a>
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
       



	   
	    <h2><?= $lang['characters'] ?></h2>
		<div class="characters-info">
		<div class="character-grid">
			<?php
			require_once 'ind/db.php';
			$conn = getDbConnection();
			$login = $conn->real_escape_string($_SESSION['username']);
		
			$sql = "SELECT char_name, race FROM characters WHERE account_name = '$login' ORDER BY char_slot ASC LIMIT 7";

			$result = $conn->query($sql);
		
			// Mapeia raças para imagens
			$races = ['Humano', 'Elfo', 'DarkElf', 'Orc', 'Drawn'];
			$count = 0;
		
			if ($result->num_rows > 0) {
				while ($char = $result->fetch_assoc()) {
					$raceIndex = (int)$char['race'];
					$raceName = $races[$raceIndex] ?? 'Humano';
					$imgPath = "img/races/{$raceName}.jpg";
					echo "
					<div class='character-card filled'
						data-name='" . htmlspecialchars($char['char_name']) . "'
						
						data-race='{$raceName}'>
						<img src='{$imgPath}' alt='{$raceName}' />
					</div>";
					$count++;
				}
			}
		
			// Preencher os slots restantes com cards vazios
			for ($i = $count; $i < 7; $i++) {
				echo "
				<div class='character-card empty'>
					<div class='placeholder'></div>
				</div>";
			}
			?>
		</div>
		</div>



	   
<div class="name-change-container">
    <h2><?= $lang['change_titulo'] ?? 'Trocar Nome do Personagem' ?></h2>
    
    <!-- Exibe o personagem selecionado -->
    <p style="color: #eec889; font-size: 1.1rem;">
        <?= $lang['service_character_selected'] ?? 'Personagem selecionado' ?>: 
        <strong id="charName">Nenhum</strong>
    </p>

    <form method="POST" action="process_name_change.php">
        <!-- Nome do personagem selecionado (preenchido via JS) -->
        <input type="hidden" name="old_char_name" id="inputCharName" required />
        
        <input type="text" name="new_char_name" maxlength="16" placeholder="<?= $lang['new_name'] ?? 'Novo nome' ?>" required />
        <button type="submit"><?= $lang['changeName'] ?? 'Trocar Nome' ?></button>
    </form>
	
		   <?php if (isset($_GET['msg'])): ?>
    <div class="msg-container <?= htmlspecialchars($_GET['tipo']) ?>">
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>

</div>





</div>



        </section>
    </main>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const cards = document.querySelectorAll(".character-card.filled");
  const inputCharName = document.getElementById("inputCharName");
  const charName = document.getElementById("charName");

  function updateCharacterDetails(card) {
    cards.forEach(c => c.classList.remove("selected"));
    card.classList.add("selected");

    const name = card.dataset.name;
    charName.textContent = name;
    inputCharName.value = name;
  }

  cards.forEach(card => {
    card.addEventListener("click", function () {
      updateCharacterDetails(this);
    });
  });

  if (cards.length > 0) {
    updateCharacterDetails(cards[0]); // Seleciona o primeiro personagem por padrão
  }
});
</script>


</body>
</html>
