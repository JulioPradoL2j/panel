<?php
session_start();
require_once 'ind/db.php';

// Definir idioma padrão
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'pt';
}

// Trocar idioma via GET
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: checkout.php");
    exit;
}

// Carregar arquivo de idioma
$lang = include "lang/{$_SESSION['lang']}.php";

// Proteção de login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$paymentId = $_GET['payment_id'] ?? null;
if (!$paymentId) {
    echo "ID de pagamento não fornecido.";
    exit;
}

$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM pix_payments WHERE payment_id = ?");
$stmt->bind_param("s", $paymentId);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();

if (!$payment) {
    echo "Pagamento não encontrado.";
    exit;
}

$qrBase64 = $payment['qr_code_base64'] ?? null;

// Dados de usuário e fatura (como você já tinha)
// Dados de usuário e fatura (como você já tinha)
$username = $_SESSION['username'] ?? 'Usuário';

require_once 'ind/db.php';
$config = require_once 'includes/config.php';
$conn = getDbConnection();


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



?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	  <meta charset="UTF-8" />
	  <title>Painel UCP - Lineage II</title>
	  <link rel="stylesheet" href="templates/dark/assets/auth.css" />
	  <link rel="stylesheet" href="templates/dark/assets/dark.css" />
	  <link rel="shortcut icon" href="icon/favicon.png">
	  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
	  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
	  <style>
	  #qrCode {
            margin-top: 20px;
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
        <h2><?= $lang['characters'] ?></h2>
		<div class="characters-info">
		<div class="character-grid">
			<?php
			require_once 'ind/db.php';
			$conn = getDbConnection();
			$login = $conn->real_escape_string($_SESSION['username']);
		
			$sql = "SELECT char_name, level, title, sex, exp, sp, karma, pvpkills, pkkills, online, onlinetime, vip, nobless, race, classid FROM characters WHERE account_name = '$login' ORDER BY char_slot ASC LIMIT 7";

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
						data-level='{$char['level']}'
						data-title='" . htmlspecialchars($char['title']) . "'
						data-sex='{$char['sex']}'
						data-exp='{$char['exp']}'
						data-sp='{$char['sp']}'
						data-karma='{$char['karma']}'
						data-pvpkills='{$char['pvpkills']}'
						data-pkkills='{$char['pkkills']}'
						data-online='{$char['online']}'
						data-onlinetime='{$char['onlinetime']}'
						data-vip='{$char['vip']}'
						data-nobless='{$char['nobless']}'
						data-race='{$raceName}'
						data-classid='{$char['classid']}'>
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
		<center>
			<h2>Escaneie o QR Code para pagar</h2>
			
			<div id="qrCode">
				<?php if ($qrBase64): ?>
					<img src="data:image/png;base64,<?= htmlspecialchars($qrBase64) ?>" alt="QR Code PIX" width="200">
				<?php else: ?>
					<p>QR Code não disponível.</p>
				<?php endif; ?>
			</div>
			
			<div id="statusMessage">Aguardando pagamento...</div>
				</section>
				</main>
			</div>
  	</center>


 
<script>
const paymentId = <?= json_encode($paymentId) ?>;

let attempts = 0;
const maxAttempts = 300; // 5 minutos

function checkStatus() {
    if (attempts >= maxAttempts) {
        document.getElementById("statusMessage").innerHTML = "❌ Tempo expirado. Tente novamente.";
        cancelPayment(paymentId); // cancela o pagamento automaticamente
        return;
    }

    fetch(`check_status.php?payment_id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "approved") {
                document.getElementById("statusMessage").innerHTML = data.processed
                    ? "✅ Pagamento confirmado e entregue!"
                    : "✅ Pagamento confirmado! Entregando recompensa...";

                setTimeout(() => {
                    window.location.href = "dashboard.php";
                }, 3000);
            } else {
                attempts++;
                setTimeout(checkStatus, 5000);
            }
        })
        .catch(error => {
            
            attempts++;
            setTimeout(checkStatus, 5000);
        });
}

function cancelPayment(paymentId) {
	
	console.log("Cancelando pagamento com ID:", paymentId); // ← Adicione isso
	
    fetch(`cancel_payment_pix.php?payment_id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("statusMessage").innerHTML = "❌ Pagamento cancelado.";
            } else {
                document.getElementById("statusMessage").innerHTML = "❌ Erro ao cancelar o pagamento.";
            }
        })
        .catch(error => {
            console.error("Erro ao cancelar pagamento:", error);
            document.getElementById("statusMessage").innerHTML = "❌ Erro ao cancelar o pagamento.";
        });
}

// ✅ Correto: tudo dentro de 1 só DOMContentLoaded
document.addEventListener("DOMContentLoaded", function () {
    // iniciar verificação de pagamento
    checkStatus();

    // lógica dos personagens
    const cards = document.querySelectorAll(".character-card.filled");
    const charName = document.getElementById("charName");
    const charRace = document.getElementById("charRace");
    const charClass = document.getElementById("charClass");

    function updateCharacterDetails(card) {
        cards.forEach(c => c.classList.remove("selected"));
        card.classList.add("selected");

        charName.textContent = card.dataset.name;
        charRace.textContent = card.dataset.race;

        const classId = parseInt(card.dataset.classid);
        charClass.textContent = classTypeMap[classId] || "Desconhecido";
    }

    cards.forEach(card => {
        card.addEventListener("click", function () {
            updateCharacterDetails(this);
        });
    });

    if (cards.length > 0) {
        updateCharacterDetails(cards[0]);
    }
});
</script>

</body>
</html>
