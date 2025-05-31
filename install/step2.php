<?php
if (file_exists('../includes/config.php')) {
    header("Location: ../index.php");
    exit;
}

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['setup'] = [
        'db_host' => $_POST['db_host'],
        'db_name' => $_POST['db_name'],
        'db_user' => $_POST['db_user'],
        'db_pass' => $_POST['db_pass'],
        'project' => $_POST['project'],
        'chronicle' => $_POST['chronicle'],
    ];
} elseif (!isset($_SESSION['setup'])) {
    header("Location: step1.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Instalação - Etapa 2</title>
<link rel="stylesheet" href="assets/style.css">
<link rel="shortcut icon" href="../icon/favicon.png">
<script>
function toggleInput(id, checkbox) {
    document.getElementById(id).style.display = checkbox.checked ? 'block' : 'none';
}
</script>
</head>
<body class="dark">
<div class="install-container">
    <h1>Configurações do Site</h1>
    <form method="post" action="finish.php">
        <label for="site_url">URL do site (ex: https://l2fun.com):</label>
        <input type="text" id="site_url" name="site_url" placeholder="https://seusite.com" required>

        <label for="theme">Tema:</label>
        <select id="theme" name="theme" required>
            <option value="dark">Escuro</option>
        </select>

        <hr>

        <label><input type="checkbox" name="use_mercadopago" onchange="toggleInput('mercadoDiv', this)"> Usar Mercado Pago?</label>
        <div id="mercadoDiv" style="display:none;">
            <input type="text" name="mercadopago_private_key" placeholder="Chave Privada Mercado Pago">
        </div>

        <label><input type="checkbox" name="use_stripe" onchange="toggleInput('stripeDiv', this)"> Usar Stripe?</label>
        <div id="stripeDiv" style="display:none;">
            <input type="text" name="stripe_private_key" placeholder="Chave Privada Stripe">
        </div>
		
		<div class="donation-section">
			<div class="section-title">Doações</div>
			<label for="donation_values">Valores de doação (separados por vírgula, ex: 0.5,1,10,20):</label>
			<input type="text" name="donation_values" placeholder="Ex: 0.5,1,10,20,50,100" required>
		</div>
		
		<div class="bonus-section">
			<div class="section-title">Bônus</div>
			<label><input type="checkbox" name="use_bonus" onchange="toggleInput('bonusConfig', this)"> Ativar bônus?</label>
			<div id="bonusConfig" style="display:none;">
				<label for="bonus_threshold">A partir de quanto aplicar o bônus?</label>
				<input type="number" name="bonus_threshold" step="0.01" placeholder="Ex: 100">
		
				<label for="bonus_percent">% de bônus a aplicar (ex: 10 para 10%)</label>
				<input type="number" name="bonus_percent" placeholder="Ex: 10">
			</div>
		</div>


        <button type="submit">Finalizar Instalação</button>
    </form>
</div>
</body>
</html>
