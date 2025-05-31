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
    header("Location: shop.php");
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



.shop-wrapper {
	margin-top: -60px;
    display: flex;
    height: 85%;
    overflow: hidden;
    color: #ccc;
}

.sidebar-filters {
    width: 300px;
    padding: 15px;
    background-color: #111;
    border-right: 1px solid #333;
    box-sizing: border-box;
    overflow-y: auto;
}

.sidebar-filters h4 {
    margin-top: 15px;
    margin-bottom: 5px;
    font-size: 10px;
    color: #fff;
    border-bottom: 1px solid #555;
    padding-bottom: 3px;
}

.sidebar-filters input[type="text"] {
    width: 85%;
    padding: 6px;
    margin-bottom: 10px;
    background-color: #222;
    color: #ccc;
    border: 1px solid #444;
}

.sidebar-filters label {
    display: block;
    font-size: 14px;
    margin-bottom: 4px;
    cursor: pointer;
}

.sidebar-filters input[type="radio"] {
    margin-right: 5px;
}

.sidebar-filters button {
    margin-top: 10px;
    width: 100%;
    padding: 8px;
    background-color: #333;
    color: #fff;
    border: none;
    cursor: pointer;
    transition: 0.3s;
}
.sidebar-filters button:hover {
    background-color: #555;
}

.main-shop {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: #0b0b0b;
    overflow: hidden;
}

.shop-header {
    display: grid;
    grid-template-columns: 80px 1fr 100px;
    padding: 10px;
    font-weight: bold;
    background-color: #1a1a1a;
    color: #fff;
    border-bottom: 1px solid #333;
}

.shop-item-container {
    flex: 1;
    overflow-y: auto;
}

.shop-item {
    display: grid;
    grid-template-columns: 80px 1fr 100px;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #333;
    color: #ddd;
}

.shop-item img {
    width: 48px;
    height: 48px;
}

.shop-item button {
    padding: 6px 10px;
    background-color: #226622;
    color: #fff;
    border: none;
    cursor: pointer;
    transition: 0.3s;
}
.shop-item button:hover {
    background-color: #2a8a2a;
}

.sidebar-filters button {
    display: none;
}

.shop-table {
    width: 100%;
    border-collapse: collapse;
    background: #111;
    box-shadow: 0 0 10px #000;
}

.shop-table th, .shop-table td {
    padding: 10px;
    border: 1px solid #333;
    text-align: center;
}

.shop-table th {
    background-color: #1d1d1d;
    color: #ffd700;
    font-weight: bold;
}

.shop-table td img {
    vertical-align: middle;
}

.buy-button {
    padding: 5px 10px;
    background-color: #0a0;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.buy-button:hover {
    background-color: #0c0;
}

.pagination {
    text-align: center;
    margin-top: 20px;
}

.pagination a {
    margin: 0 5px;
    text-decoration: none;
    color: #aaa;
}

.pagination .active-page {
    font-weight: bold;
    color: #ffd700;
}
/* Modal base */
.modal {
  display: flex;
  justify-content: center;
  align-items: center;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.7);
}

/* Conteúdo do modal */
.modal-content {
  background-color: #1a1a1a;
  color: #fff;
  padding: 20px 30px;
  border-radius: 12px;
  box-shadow: 0 0 12px #ffaa00;
  width: 400px;
  max-width: 95%;
  text-align: center;
  font-family: 'Orbitron', sans-serif;
}

.modal-content h2 {
  color: #ffaa00;
  margin-bottom: 15px;
}

.modal-content img {
  margin-bottom: 10px;
}

.modal-content button {
  margin: 10px 5px 0;
  padding: 8px 16px;
  font-weight: bold;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.modal-content button[type="submit"] {
  background-color: #28a745;
  color: white;
}

.modal-content button[type="button"] {
  background-color: #dc3545;
  color: white;
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
<br>
<section class="content">

    <div class="shop-wrapper">

 <!-- FILTROS LATERAIS -->
<div class="sidebar-filters">
    <form method="GET" class="filters">
        <input type="text" name="search" placeholder="Buscar item..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

        <h4>Tipo</h4>
        <?php
        $tipos = ['Weapon' => 'Arma', 'Armor' => 'Armadura', 'Jewel' => 'Joia', 'Other' => 'Outros'];
        foreach ($tipos as $value => $label) {
            $checked = ($_GET['type'] ?? '') === $value ? 'checked' : '';
            echo "<label><input type='radio' name='type' value='$value' onchange='this.form.submit()' $checked> $label</label><br>";
        }
        ?>

        <h4>Grau</h4>
        <?php
        $grades = ['No-Grade', 'D', 'C', 'B', 'A', 'S'];
        foreach ($grades as $grade) {
            $checked = ($_GET['grade'] ?? '') === $grade ? 'checked' : '';
            echo "<label><input type='radio' name='grade' value='$grade' onchange='this.form.submit()' $checked> $grade</label><br>";
        }
        ?>

       
    </form>
	 <button type="submit">Filtrar</button>
</div>

<!-- LISTAGEM DE ITENS -->
<div class="main-shop">
    <table class="shop-table">
        <thead>
            <tr>
                <th>Icon</th>
                <th>Name</th>
                <th>Prince</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
		


// Buscar personagens do usuário para o select
$stmtChar = $conn->prepare("SELECT char_name FROM characters WHERE account_name = ?");
$stmtChar->bind_param("s", $username);
$stmtChar->execute();
$resChar = $stmtChar->get_result();
$chars = $resChar->fetch_all(MYSQLI_ASSOC);
$stmtChar->close();



	$itensPorPagina = 9;
$paginaAtual = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;



    $query = "SELECT * FROM shop_items WHERE available = 1";
    $params = [];

    // Monta base do WHERE
$where = " WHERE available = 1";
$params = [];
$types = "";

// Filtros
if (!empty($_GET['search'])) {
    $where .= " AND name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
    $types .= "s";
}
if (!empty($_GET['type'])) {
    $where .= " AND type = ?";
    $params[] = $_GET['type'];
    $types .= "s";
}
if (!empty($_GET['grade'])) {
    $where .= " AND grade = ?";
    $params[] = $_GET['grade'];
    $types .= "s";
}

// Conta total de itens com filtros aplicados
$countStmt = $conn->prepare("SELECT COUNT(*) FROM shop_items" . $where);
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countStmt->bind_result($totalItens);
$countStmt->fetch();
$countStmt->close();

$totalPaginas = ceil($totalItens / $itensPorPagina);

// Busca itens da página atual
$query = "SELECT * FROM shop_items" . $where . " LIMIT ? OFFSET ?";
$params[] = $itensPorPagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


        while ($item = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td><img src='icon/" . htmlspecialchars($item['icon']) . "' alt='" . htmlspecialchars($item['name']) . "' width='32'></td>";
            echo "<td>" . htmlspecialchars($item['name']) . "</td>";
            echo "<td>R$ " . number_format($item['price'], 2, ',', '.') . "</td>";
           
			echo "<td>
			<button class='buy-button' 
				data-id='{$item['id']}'
				data-name='" . htmlspecialchars($item['name']) . "'
				data-quantity='{$item['quantity']}'
				data-price='{$item['price']}'
				data-icon='{$item['icon']}'
				onclick='abrirModalCompra(this)'>
				Buy
			</button>
		</td>";

            echo "</tr>";
        }
        ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <div class="pagination">
        <?php if ($paginaAtual > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $paginaAtual - 1])) ?>">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $paginaAtual ? 'active-page' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($paginaAtual < $totalPaginas): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $paginaAtual + 1])) ?>">Próxima &raquo;</a>
        <?php endif; ?>
    </div>
</div>


</div>


    </div>

</section>

    </main>
</div>


<center>
<div id="modalCompra" class="modal" style="display:none;">
  <div class="modal-content">
    <h2>Confirmar Compra</h2>
    <img id="itemIcon" src="" alt="Ícone do Item" width="48">
    <p><strong id="itemName"></strong></p>
    <p>Quantidade: <span id="itemQuantity"></span></p>
    <p>Preço: R$ <span id="itemPrice"></span></p>

<form id="formCompra" action="comprar.php" method="POST" onsubmit="return enviarCompra(event)">
  <!-- ID e name devem estar iguais -->
  <input type="hidden" name="itemid" id="formItemId">

  <label for="char">Personagem:</label>
  <select name="char" id="char" onchange="mostrarPersonagemSelecionado()" required>
    <option value="" disabled selected>Selecione</option>
    <?php foreach ($chars as $char): ?>
      <option value="<?= htmlspecialchars($char['char_name']) ?>"><?= htmlspecialchars($char['char_name']) ?></option>
    <?php endforeach; ?>
  </select>

  <p>Selecionado: <strong id="personagemSelecionado">Nenhum</strong></p>

  <div>
    <button type="submit">Confirmar</button>
    <button type="button" onclick="fecharModal()">Cancelar</button>
  </div>
</form>


  </div>
</div>

</center>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.querySelector('input[name="search"]');
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const gradeRadios = document.querySelectorAll('input[name="grade"]');
    const shopItems = document.querySelectorAll('.shop-item');

    function normalize(text) {
        return text.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }

    function filterShop() {
        const search = normalize(searchInput?.value || "");
        const selectedType = document.querySelector('input[name="type"]:checked')?.value || "";
        const selectedGrade = document.querySelector('input[name="grade"]:checked')?.value || "";

        shopItems.forEach(item => {
            const name = normalize(item.querySelector('strong')?.textContent || "");
            const grade = normalize(item.querySelector('span:nth-child(2)')?.textContent || "");
            const type = normalize(item.dataset.type || "");
            const matchesSearch = name.includes(search);
            const matchesType = !selectedType || type === normalize(selectedType);
            const matchesGrade = !selectedGrade || grade.includes(normalize(selectedGrade));

            item.style.display = (matchesSearch && matchesType && matchesGrade) ? "grid" : "none";
        });
    }

    if (searchInput) searchInput.addEventListener("input", filterShop);
    typeRadios.forEach(radio => radio.addEventListener("change", filterShop));
    gradeRadios.forEach(radio => radio.addEventListener("change", filterShop));

    setupToggleRadios("type");
    setupToggleRadios("grade");
});

function mostrarPersonagemSelecionado() {
    const select = document.getElementById("char");
    const display = document.getElementById("personagemSelecionado");
    display.textContent = select.value || "Nenhum";
}


// Permitir desmarcar radios ao clicar novamente
function setupToggleRadios(name) {
    const radios = document.querySelectorAll(`input[name="${name}"]`);
    let lastChecked = null;

    radios.forEach(radio => {
        radio.addEventListener('click', function () {
            if (lastChecked === this) {
                this.checked = false;
                lastChecked = null;
                document.dispatchEvent(new Event("DOMContentLoaded")); // força atualização
            } else {
                lastChecked = this;
            }
        });
    });
}
function abrirModalCompra(button) {
    const id = button.dataset.id;
    const name = button.dataset.name;
    const quantity = button.dataset.quantity;
    const price = parseFloat(button.dataset.price).toFixed(2).replace('.', ',');
    const icon = button.dataset.icon;

    document.getElementById('formItemId').value = id;
    document.getElementById('itemName').textContent = name;
    document.getElementById('itemQuantity').textContent = quantity;
    document.getElementById('itemPrice').textContent = price;
    document.getElementById('itemIcon').src = 'icon/' + icon;

    document.getElementById('modalCompra').style.display = 'flex';
}



function fecharModal() {
   const modal = document.getElementById('modalCompra');

    if (modal) modal.style.display = 'none';
}

function enviarCompra(event) {
    event.preventDefault();

    const itemid = document.getElementById('formItemId').value;
    const char = document.getElementById('char').value;

    if (!itemid || !char) {
        alert("❌ Preencha todos os campos.");
        return false;
    }

    const formData = new FormData();
    formData.append('itemid', itemid);
    formData.append('char', char);

    fetch('comprar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log("Resposta bruta:", text);
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error("Erro ao converter resposta em JSON: " + e.message + "\nResposta: " + text);
        }
    })
    .then(result => {
        if (result.success) {
            alert('✅ Compra realizada com sucesso!');
            fecharModal();
            window.location.href = 'shop.php';  // <- redireciona aqui
        } else {
            alert('❌ Erro: ' + result.message);
        }
    })
    .catch(error => {
        console.error("Erro final:", error);
        alert('❌ Erro inesperado. Tente novamente.');
    });

    return false;
}

</script>





</body>
</html>
