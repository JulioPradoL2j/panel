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
// Dados de usuário e fatura (como você já tinha)
$username = $_SESSION['username'] ?? 'Usuário';

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

$accessLevel = (int) $user['access_level'];


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



// Busca todos os itens
$result = $conn->query("SELECT * FROM shop_items ORDER BY id DESC");
$items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Ícones disponíveis (sem filtro de extensão)
$icons = array_filter(scandir('../icon'), function($f) {
    return !in_array($f, ['.', '..']) && is_file("../icon/$f");
});
// Verifica se está em modo edição
$editItem = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM shop_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editItem = $res->fetch_assoc();
    $stmt->close();
}

// Salvar item (add ou update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = $_POST['name'];
    $description = $_POST['description'] ?? '';
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $grade = $_POST['grade'];
    $type = $_POST['type'];
    $icon = $_POST['icon'];
    $available = isset($_POST['available']) ? 1 : 0;
    $item_id = intval($_POST['itemid']);
    $stackable = isset($_POST['stackable']) ? 1 : 0;

    if ($id) {
        $stmt = $conn->prepare("UPDATE shop_items SET name=?, description=?, quantity=?, price=?, grade=?, type=?, icon=?, available=?, item_id=?, stackable=? WHERE id=?");
        $stmt->bind_param("ssidsssiiii", $name, $description, $quantity, $price, $grade, $type, $icon, $available, $item_id, $stackable, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO shop_items (name, description, quantity, price, grade, type, icon, available, item_id, stackable) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssidsssiii", $name, $description, $quantity, $price, $grade, $type, $icon, $available, $item_id, $stackable);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: gerenciar.php");
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
   


    .item-form {
    background: #2a2a2a;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px #000;
}

.form-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.form-grid label {
    display: flex;
    flex-direction: column;
    font-size: 0.95rem;
    flex: 1 1 220px;
}

.form-grid label.full {
    flex: 1 1 100%;
}

input, select, textarea {
    background: #333;
    color: #fff;
    border: 1px solid #555;
    padding: 8px;
    border-radius: 4px;
    font-size: 0.9rem;
    margin-top: 5px;
}

textarea {
    min-height: 80px;
    resize: vertical;
}

button {
    background-color: #f5c261;
    color: #000;
    border: none;
    padding: 12px;
    font-size: 1rem;
    border-radius: 6px;
    margin-top: 15px;
    width: 100%;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #e0ac45;
}

.checkbox-label {
    flex-direction: row;
    align-items: center;
    margin-top: 25px;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 8px;
}

.icon-preview {
    display: flex;
    align-items: flex-end;
    padding-top: 25px;
}

.icon-preview img {
    width: 48px;
    height: 48px;
    border: 1px solid #666;
    border-radius: 4px;
    background-color: #111;
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        flex-direction: column;
    }
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
			<h3>Gerenciar Itens da Loja</h3>
			
			<form method="POST" class="item-form">
				<?php if ($editItem): ?>
					<input type="hidden" name="id" value="<?= htmlspecialchars($editItem['id']) ?>">
				<?php endif; ?>
			
				<div class="form-grid">
					<label>
						Nome:
						<input type="text" name="name" required value="<?= htmlspecialchars($editItem['name'] ?? '') ?>">
					</label>
			
					<label>
						Quantidade:
						<input type="number" name="quantity" value="<?= htmlspecialchars($editItem['quantity'] ?? 1) ?>">
					</label>
			
					<label>
						ID do Item:
						<input type="number" name="itemid" value="<?= htmlspecialchars($editItem['itemid'] ?? 0) ?>">
					</label>
			
					<label>
						Preço (R$):
						<input type="number" step="0.01" name="price" value="<?= htmlspecialchars($editItem['price'] ?? 0) ?>">
					</label>
			
					<label>
						Grau:
						<select name="grade">
							<?php foreach (['No-Grade','D','C','B','A','S'] as $g): ?>
								<option value="<?= $g ?>" <?= ($editItem['grade'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
							<?php endforeach; ?>
						</select>
					</label>
			
					<label>
						Tipo:
						<select name="type">
							<?php foreach (['Weapon','Armor','Jewel','Other'] as $t): ?>
								<option value="<?= $t ?>" <?= ($editItem['type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
							<?php endforeach; ?>
						</select>
					</label>
			
					<label class="checkbox-label">
						<input type="checkbox" name="available" value="1" <?= !isset($editItem) || !empty($editItem['available']) ? 'checked' : '' ?>>
						Disponível
					</label>
			
					<label class="checkbox-label">
						<input type="checkbox" name="stackable" value="1" <?= isset($editItem['stackable']) && $editItem['stackable'] ? 'checked' : '' ?>>
						Stackable
					</label>
			
					<label class="full">
						Descrição:
						<textarea name="description"><?= htmlspecialchars($editItem['description'] ?? '') ?></textarea>
					</label>
			
					<label>
						Ícone:
						<select name="icon" onchange="document.getElementById('preview').src = '../icon/' + this.value">
							<option value="">-- Selecione um ícone --</option>
							<?php foreach ($icons as $icon): ?>
								<option value="<?= $icon ?>" <?= ($editItem['icon'] ?? '') === $icon ? 'selected' : '' ?>><?= $icon ?></option>
							<?php endforeach; ?>
						</select>
					</label>
			
					<div class="icon-preview">
						<img id="preview" src="<?= isset($editItem['icon']) ? '../icon/' . htmlspecialchars($editItem['icon']) : '' ?>" width="48" height="48" alt="Prévia do ícone">
					</div>
				</div>
			
				<button type="submit"><?= $editItem ? 'Atualizar' : 'Adicionar' ?> Item</button>
			</form>
			
			<?php else: ?>
				<p style="color: red;">Apenas administradores podem gerenciar a loja.</p>
			<?php endif; ?>




        </main>
    </div>
	

</body>
</html>
