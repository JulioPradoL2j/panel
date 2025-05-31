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
$accessLevel = (int) $user['access_level'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $id = intval($_POST['id']);
    $item_id = intval($_POST['itemid']);
    $icon = $_POST['icon'];
    $name = $_POST['name'];
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $type = $_POST['type'];
    $grade = $_POST['grade'];
$available = intval($_POST['available']);
$stackable = intval($_POST['stackable']);


    $stmt = $conn->prepare("UPDATE shop_items SET item_id=?, icon=?, name=?, quantity=?, price=?, type=?, grade=?, available=?, stackable=? WHERE id=?");
    $stmt->bind_param("issidsssii", $item_id, $icon, $name, $quantity, $price, $type, $grade, $available, $stackable, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}




// Busca todos os itens
// Paginação
$itensPorPagina = 6;
$paginaAtual = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Conta total de itens
$totalItens = $conn->query("SELECT COUNT(*) as total FROM shop_items")->fetch_assoc()['total'];
$totalPaginas = ceil($totalItens / $itensPorPagina);

// Busca apenas os itens da página atual
$stmt = $conn->prepare("SELECT * FROM shop_items ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $itensPorPagina, $offset);
$stmt->execute();
$result = $stmt->get_result();
$items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();



// Ícones disponíveis (sem filtro de extensão)
$icons = array_filter(scandir('../icon'), function($f) {
    return !in_array($f, ['.', '..']) && is_file("../icon/$f");
});


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
	
	
	
       input, select {
    padding: 8px;
    background: #333;
    color: #fff;
    border: 1px solid #555;
    border-radius: 5px;
}
button {
    background: #f5c261;
    color: #000;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}


        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: -20px;
            font-size: 0.95em;
        }

        table th, table td {
            border: 1px solid #444;
            padding: 8px;
            text-align: center;
        }

        table th {
            background-color: #2c2c2c;
        }

        img#preview {
            display: block;
            margin: 10px 0;
        }

        a {
            color: #f5c261;
            text-decoration: none;
            font-weight: bold;
        }
.input-field {
    width: 100%;
    margin-bottom: 10px;
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
		  
		    <h3>Itens Cadastrados</h3>
			
			   	<form method="GET" style="margin-bottom: 20px;">
<input type="text" id="filtroItens" placeholder="🔍 Buscar item..." style="margin-bottom: 15px; padding: 10px; width: 100%; max-width: 400px; border: 1px solid #555; background: #222; color: #fff; border-radius: 8px;">


</form>


        <table>
            <thead>
                <tr>
                    <th>Ícone</th><th>Nome</th><th>Qtd</th><th>Preço</th><th>Tipo</th><th>Grau</th><th>Status</th><th>Stackable</th><th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><img src="../icon/<?= htmlspecialchars($item['icon']) ?>" width="32"></td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td>R$ <?= number_format($item['price'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($item['type']) ?></td>
                        <td><?= htmlspecialchars($item['grade']) ?></td>
                        <td><?= $item['available'] ? '✔' : '✖' ?></td>
						<td><?= $item['stackable'] ? '✔' : '✖' ?></td>
                        <td><a href="?edit=<?= $item['id'] ?>">Editar</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
<div style="margin-top: 20px; text-align: center;">
    <?php if ($paginaAtual > 1): ?>
        <a href="?page=<?= $paginaAtual - 1 ?>" style="margin-right: 10px;">&laquo; Anterior</a>
    <?php endif; ?>
    
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
        <a href="?page=<?= $i ?>" style="<?= $i == $paginaAtual ? 'font-weight:bold; color:#ffd700;' : '' ?> margin: 0 5px;"><?= $i ?></a>
    <?php endfor; ?>
    
    <?php if ($paginaAtual < $totalPaginas): ?>
        <a href="?page=<?= $paginaAtual + 1 ?>" style="margin-left: 10px;">Próxima &raquo;</a>
    <?php endif; ?>
</div>

        </main>
    </div>
	

	
	<?php if (isset($_GET['edit']) && is_numeric($_GET['edit'])): ?>
    <?php if ($accessLevel === 1): 
        $editId = intval($_GET['edit']);
        $stmt = $conn->prepare("SELECT * FROM shop_items WHERE id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $itemEdit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    ?>
        <?php if ($itemEdit): ?>
           <div id="editModal" style="
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.85);
    display: flex; justify-content: center; align-items: center;
    z-index: 9999;
    overflow-y: auto;
    padding: 20px;
">
    <form method="post" action="" style="background: #222;color: #fff;padding: 30px;border-radius: 10px;width: 100%;max-width: 650px; box-sizing: border-box; margin-top: -30px; ">
    <h2 style="grid-column: span 2;">Editar Item</h2>
    <input type="hidden" name="id" value="<?= $itemEdit['id'] ?>">

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">

        <div>
            <label>Nome:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($itemEdit['name']) ?>" style="width: 100%;">
        </div>

        <div>
            <label>ID do Item:</label>
            <input type="number" name="itemid" value="<?= $itemEdit['item_id'] ?>" style="width: 100%;">
        </div>

        <div>
            <label>Quantidade:</label>
            <input type="number" name="quantity" value="<?= $itemEdit['quantity'] ?>" style="width: 100%;">
        </div>

        <div>
            <label>Preço (R$):</label>
            <input type="text" name="price" value="<?= $itemEdit['price'] ?>" style="width: 100%;">
        </div>

        <div style="grid-column: span 2;">
            <label>Ícone:</label>
            <select name="icon" style="width: 100%;">
                <?php foreach ($icons as $icon): ?>
                    <option value="<?= $icon ?>" <?= $itemEdit['icon'] === $icon ? 'selected' : '' ?>>
                        <?= $icon ?>
                    </option>
                <?php endforeach; ?>
            </select>
          <center>  <img id="preview" src="../icon/<?= htmlspecialchars($itemEdit['icon']) ?>" width="32" style="margin-top: 5px;"> </center>
        </div>

        <div>
            <label>Tipo:</label>
            <select name="type" style="width: 100%;">
                <?php foreach (['Weapon', 'Armor', 'Jewel', 'Other'] as $tipo): ?>
                    <option value="<?= $tipo ?>" <?= $itemEdit['type'] == $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Grau:</label>
            <select name="grade" style="width: 100%;">
                <?php foreach (['No-Grade','D','C','B','A','S'] as $grau): ?>
                    <option value="<?= $grau ?>" <?= $itemEdit['grade'] == $grau ? 'selected' : '' ?>><?= $grau ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="grid-column: span 2;">
            <label>Status:</label>
            <select name="available" style="width: 100%;">
                <option value="1" <?= $itemEdit['available'] ? 'selected' : '' ?>>✔ Ativo</option>
                <option value="0" <?= !$itemEdit['available'] ? 'selected' : '' ?>>✖ Inativo</option>
            </select>
        </div>
		
		<div style="grid-column: span 2;">
            <label>Stackable:</label>
            <select name="stackable" style="width: 100%;">
                <option value="1" <?= $itemEdit['stackable'] ? 'selected' : '' ?>>✔ Ativo</option>
                <option value="0" <?= !$itemEdit['stackable'] ? 'selected' : '' ?>>✖ Inativo</option>
            </select>
        </div>

    </div>
<?php if (isset($itemEdit['id'])): ?>
    <input type="hidden" name="id" value="<?= $itemEdit['id'] ?>">
<?php endif; ?>

    <div style="margin-top: 20px; text-align: right;">
        <button type="submit" name="update_item" style="padding: 10px 20px;">Salvar</button>
        <a href="?" style="margin-left: 10px; color: #fff;">Cancelar</a>
    </div>
</form>
        <?php endif; ?>
    <?php else: ?>
        <div style="
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85);
            display: flex; justify-content: center; align-items: center;
            z-index: 9999;
        ">
            <div style="
                background: #2c2c2c;
                color: #ff4f4f;
                padding: 30px;
                border-radius: 10px;
                text-align: center;
                max-width: 500px;
            ">
                <h2>Acesso Negado</h2>
                <p>Você não tem permissão para editar itens da loja.</p>
                <a href="?" style="color: #fff; display: inline-block; margin-top: 15px;">Voltar</a>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>





<script>
document.querySelector("select[name='icon']").addEventListener("change", function () {
    document.getElementById("preview").src = "../icon/" + this.value;
});
</script>
<script>
document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        window.location.href = "?"; // Fecha o modal
    }
});
</script>
<script>
document.getElementById("filtroItens").addEventListener("keyup", function () {
    let filtro = this.value.toLowerCase();
    let linhas = document.querySelectorAll("table tbody tr");

    linhas.forEach(function (linha) {
        let texto = linha.innerText.toLowerCase();
        linha.style.display = texto.includes(filtro) ? "" : "none";
    });
});
</script>

</body>
</html>
