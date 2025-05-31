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
    header("Location: donate.php");
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

    // Consulta do accesslevel
    $stmt = $conn->prepare("SELECT accesslevel FROM accounts WHERE login = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $_SESSION['accesslevel'] = (int)$row['accesslevel'];
    }

    $stmt->close();
}

$donationValues = explode(',', $config['donation_values']);

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

    <meta charset="UTF-8">
    <title>Painel UCP - Lineage II</title>
    <link rel="stylesheet" href="templates/dark/assets/auth.css" />
    <link rel="stylesheet" href="templates/dark/assets/dark.css" />
    <link rel="shortcut icon" href="icon/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
	<script src="admin_button.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>


.donate-box {
    background: transparent;
    border: 0px solid #2e2e2e;
    padding: 20px;
    margin-top: -93px;
    border-radius: 12px;
    color: #e6c888;
    max-width: 550px;
}

.donate-box h3 {
    margin-bottom: 10px;
    font-size: 20px;
    color: #e6c888;
}
.donate-table {
    width: 100%;
    border-collapse: collapse;
}
.donate-table td {
    padding: 10px;
    vertical-align: middle;
}
.donate-table label {
    font-weight: bold;
}
.donate-table select {
    width: 100%;
    padding: 8px;
    border-radius: 6px;
    border: none;
    background: #2e2e2e;
    color: #fff;
}
.char-display {
    font-weight: bold;
    color: #7fff7f;
}
.donate-btn {
    padding: 10px 20px;
    background-color: #e6c888;
    color: #000;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s;
}
.donate-btn:hover {
    background-color: #f5da9d;
}
</style>
</head>
<body>
<script>
  window.USER_ACCESS_LEVEL = <?= isset($_SESSION['accesslevel']) ? (int)$_SESSION['accesslevel'] : 0 ?>;
</script>
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

<!-- Estrutura da doação -->
<center>
	<div class="donate-box">
		<h3><?= $lang['donate']['donate_title'] ?? 'Realizar Doação' ?></h3>

		<form action="process_donate.php" method="post" class="donate-form">
			<input type="hidden" name="char_name" id="inputCharName" value="">

			<table class="donate-table">
				<tr>
					<td><label for="payment_method"><?= $lang['donate']['payment_method'] ?? 'Forma de Pagamento' ?>:</label></td>
					<td>
						<select name="payment_method" id="payment_method" required>
							<option value="MercadoPago">MercadoPago [Pix | Crédito]</option>
							<option value="Stripe">Stripe [Cartão Internacional]</option>
						</select>
					</td>
				</tr>
				<tr>
					<td><label for="amount"><?= $lang['donate']['amount'] ?? 'Quantia desejada' ?>:</label></td>
					<td>
						<select name="amount" id="amount" required>
							<?php foreach ($donationValues as $val): ?>
								<option value="<?= htmlspecialchars($val) ?>">R$ <?= number_format($val, 2, ',', '.') ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<td><strong><?= $lang['donate']['char_selected'] ?? 'Personagem' ?>:</strong></td>
					<td><span id="charName" class="char-display">-</span></td>
				</tr>
				<tr>
					<td><strong><?= $lang['donate']['total'] ?? 'Valor total' ?>:</strong></td>
					<td>
						<span id="totalValue" class="char-display">R$ 0,00</span>
						<span id="bonusInfo" class="char-display" style="color: #90ee90; margin-left: 10px;"></span>
					</td>
				</tr>
			</table>

			<div style="text-align: right; margin-top: 15px;">
				<button type="submit" class="donate-btn"><?= $lang['donate']['submit'] ?? 'FAZER PEDIDO' ?></button>
			</div>
		</form>
	</div>
</center>

        </section>
    </main>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const cards = document.querySelectorAll(".character-card.filled");
  const inputCharName = document.getElementById("inputCharName");
  const charName = document.getElementById("charName");
  const amountSelect = document.getElementById("amount");
  const totalValue = document.getElementById("totalValue");
  const bonusInfo = document.getElementById("bonusInfo");
  const form = document.querySelector(".donate-form");

  const paymentMethod = document.getElementById("payment_method");
  const rowFullName = document.getElementById("rowFullName");
  const rowIdentifier = document.getElementById("rowIdentifier");
  const inputFullName = document.getElementById("full_name");
  const inputIdentifier = document.getElementById("identifier");

  const bonusThreshold = <?= $config['bonus_threshold'] ?>;
  const bonusPercent = <?= $config['bonus_percent'] ?>;
  const useBonus = <?= $config['use_bonus'] ? 'true' : 'false' ?>;

  function updateTotalAndBonus() {
    const amount = parseFloat(amountSelect.value);
    if (isNaN(amount)) return;

    totalValue.textContent = 'R$ ' + amount.toFixed(2).replace('.', ',');

    if (useBonus && amount >= bonusThreshold) {
      const bonus = Math.floor(amount * (bonusPercent / 100));
      bonusInfo.textContent = `+ ${bonus} bônus`;
    } else {
      bonusInfo.textContent = '';
    }
  }

  function updateCharacterDetails(card) {
    cards.forEach(c => c.classList.remove("selected"));
    card.classList.add("selected");

    const name = card.dataset.name;
    charName.textContent = name;
    inputCharName.value = name;
  }

  function updateFieldsByMethod() {
    const isMP = paymentMethod.value === "MercadoPago";
    rowFullName.style.display = isMP ? "table-row" : "none";
    rowIdentifier.style.display = isMP ? "table-row" : "none";
    inputFullName.required = isMP;
    inputIdentifier.required = isMP;
  }

  function enviarDoacao() {
    const formData = new FormData(document.getElementById("form-doacao"));
    fetch("process_donate.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.qr_code_base64) {
        document.getElementById("qr-img").src = "data:image/png;base64," + data.qr_code_base64;
        document.getElementById("qr-img").style.display = "block";
        document.getElementById("qr-code-text").innerText = data.qr_code;
      } else {
        alert(data.error || "Erro desconhecido");
      }
    });
  }

  cards.forEach(card => {
    card.addEventListener("click", function () {
      updateCharacterDetails(this);
    });
  });

  if (cards.length > 0) {
    updateCharacterDetails(cards[0]);
  }

  amountSelect.addEventListener("change", updateTotalAndBonus);
  paymentMethod.addEventListener("change", updateFieldsByMethod);

  updateTotalAndBonus();
  updateFieldsByMethod();

  form.addEventListener("submit", function (e) {
    if (!inputCharName.value) {
      alert("Selecione um personagem antes de continuar.");
      e.preventDefault();
    }

    if (paymentMethod.value === "MercadoPago") {
      if (!inputFullName.value.trim() || !inputIdentifier.value.trim()) {
        alert("Preencha o nome completo e o CPF ou e-mail.");
        e.preventDefault();
      }
    }
  });
});
</script>



</body>
</html>
