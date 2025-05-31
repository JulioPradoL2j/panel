<?php
session_start();

if (!isset($_SESSION['setup']) || !isset($_POST['theme']) || !isset($_POST['site_url'])) {
    header("Location: step1.php");
    exit;
}

$setup = $_SESSION['setup'];
$theme = $_POST['theme'];
$site_url = rtrim($_POST['site_url'], '/');
$use_mp = isset($_POST['use_mercadopago']);

$mp_key = $_POST['mercadopago_private_key'] ?? '';
$use_stripe = isset($_POST['use_stripe']);
$stripe_key = $_POST['stripe_private_key'] ?? '';
$donation_values = $_POST['donation_values'] ?? '1,10,20,50,100';
$use_bonus = isset($_POST['use_bonus']);
$bonus_threshold = $_POST['bonus_threshold'] ?? 0;
$bonus_percent = $_POST['bonus_percent'] ?? 0;


// Conteúdo do config.php
$configContent = "<?php\nreturn [\n";
$configContent .= "    'db_host' => '{$setup['db_host']}',\n";
$configContent .= "    'db_name' => '{$setup['db_name']}',\n";
$configContent .= "    'db_user' => '{$setup['db_user']}',\n";
$configContent .= "    'db_pass' => '{$setup['db_pass']}',\n";
$configContent .= "    'project' => '{$setup['project']}',\n";
$configContent .= "    'chronicle' => '{$setup['chronicle']}',\n";
$configContent .= "    'theme' => '{$theme}',\n";
$configContent .= "    'site_url' => '{$site_url}',\n";
$configContent .= "    'use_mercadopago' => " . ($use_mp ? 'true' : 'false') . ",\n";
$configContent .= "    'mercadopago_private_key' => '{$mp_key}',\n";
$configContent .= "    'use_stripe' => " . ($use_stripe ? 'true' : 'false') . ",\n";
$configContent .= "    'stripe_private_key' => '{$stripe_key}',\n";
$configContent .= "    'donation_values' => '{$donation_values}',\n";
$configContent .= "    'use_bonus' => " . ($use_bonus ? 'true' : 'false') . ",\n";
$configContent .= "    'bonus_threshold' => {$bonus_threshold},\n";
$configContent .= "    'bonus_percent' => {$bonus_percent},\n";
$configContent .= "];\n";

$path = __DIR__ . '/../includes';
if (!file_exists($path)) {
    mkdir($path, 0777, true);
}
file_put_contents($path . '/config.php', $configContent);

unset($_SESSION['setup']);

// Agora conecta e executa as SQLs, enviando progresso via flush

// SQLs a executar
$sqls = [
    "
	
	CREATE TABLE IF NOT EXISTS pix_payments (
		id INT AUTO_INCREMENT,
		char_name VARCHAR(100) NOT NULL,
		payment_id VARCHAR(100) NOT NULL,
		qr_code TEXT NOT NULL,
		qr_code_base64 LONGTEXT,
		status VARCHAR(20) NOT NULL DEFAULT 'pending', -- 'pending', 'approved', 'cancelled'
		processed TINYINT(1) NOT NULL DEFAULT 0,       -- 0 = não entregue, 1 = entregue
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY `uniq_payment_id` (`payment_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

	;",
	
	
	"
CREATE TABLE shop_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(100),
    description TEXT,
    quantity INT DEFAULT 1,
    item_id INT DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    grade ENUM('No-Grade', 'D', 'C', 'B', 'A', 'S') DEFAULT 'No-Grade',
    type ENUM('Weapon', 'Armor', 'Jewel', 'Other') DEFAULT 'Other',
    available BOOLEAN DEFAULT TRUE,
    stackable BOOLEAN DEFAULT FALSE
);


	
	;",
	"
	
		CREATE TABLE `stripe_payments` (
		`id` INT AUTO_INCREMENT PRIMARY KEY,
		`char_name` VARCHAR(50) NOT NULL,
		`user_id` VARCHAR(50) NOT NULL,
		`session_id` VARCHAR(255) NOT NULL,
		`payment_status` VARCHAR(50) DEFAULT 'pending',
		`amount` DECIMAL(10,2) NOT NULL,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		);
	;",
	
	
	
	
	
    "CREATE TABLE IF NOT EXISTS account_balance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        login VARCHAR(50) NOT NULL UNIQUE,
        balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );"
];

function flush_echo($str) {
    echo $str . str_repeat(' ', 1024); // força flush
    @ob_flush();
    @flush();
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Finalizando Instalação</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="dark">
<div class="install-container">
    <h1>Finalizando Instalação</h1>
    <div id="progress">
<?php
// Conecta no banco para executar as queries
$mysqli = new mysqli($setup['db_host'], $setup['db_user'], $setup['db_pass'], $setup['db_name']);
if ($mysqli->connect_error) {
    flush_echo("Erro ao conectar no banco: " . $mysqli->connect_error);
    exit;
}

flush_echo("Conectado ao banco com sucesso.<br>\n");

foreach ($sqls as $i => $sql) {
    flush_echo("Executando query " . ($i+1) . " de " . count($sqls) . " ...<br>\n");
    if ($mysqli->query($sql) === TRUE) {
        flush_echo("Query " . ($i+1) . " executada com sucesso.<br><br>\n");
    } else {
        flush_echo("Erro na query " . ($i+1) . ": " . $mysqli->error . "<br><br>\n");
    }
}
$mysqli->close();

flush_echo("<b>Instalação concluída com sucesso!</b><br>");
flush_echo("Você será redirecionado para a página principal em 5 segundos...");

?>
    </div>
</div>
<script>
setTimeout(() => {
    window.location.href = '../index.php';
}, 5000);
</script>
</body>
</html>
