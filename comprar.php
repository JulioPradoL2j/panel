<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'ind/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username'], $_POST['itemid'], $_POST['char'])) {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
    exit;
}

$username = $_SESSION['username'];
$itemId = intval($_POST['itemid']);
$charName = trim($_POST['char']);

$conn = getDbConnection();

// Função para gerar object_id único
function generateUniqueObjectId(mysqli $conn): int {
    do {
        $objectId = random_int(1, 999999999);
        $stmt = $conn->prepare("SELECT 1 FROM items WHERE object_id = ? LIMIT 1");
        $stmt->bind_param("i", $objectId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    } while ($exists);
    return $objectId;
}

// Buscar saldo do usuário
$stmt = $conn->prepare("SELECT balance FROM account_balance WHERE login = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($balance);
if (!$stmt->fetch()) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    exit;
}
$stmt->close();

// Buscar item no shop
$stmt = $conn->prepare("SELECT item_id, price, quantity, stackable FROM shop_items WHERE id = ? AND available = 1");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item || $item['quantity'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Item inválido ou indisponível.']);
    exit;
}

// Buscar personagem
$stmt = $conn->prepare("SELECT obj_Id, online FROM characters WHERE account_name = ? AND char_name = ?");
$stmt->bind_param("ss", $username, $charName);
$stmt->execute();
$stmt->bind_result($charId, $online);
if (!$stmt->fetch()) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Personagem inválido.']);
    exit;
}
$stmt->close();

if ($online) {
    echo json_encode(['success' => false, 'message' => 'Personagem está online. Faça logout para receber o item.']);
    exit;
}

// Verifica saldo suficiente
if ($balance < $item['price']) {
    echo json_encode(['success' => false, 'message' => 'Saldo insuficiente.']);
    exit;
}

$conn->begin_transaction();

try {
    $gameItemId = $item['item_id'];
    $quantity = $item['quantity'];
    $stackable = (bool)$item['stackable'];

    if ($stackable) {
        // Verifica se o personagem já possui o item
        $stmt = $conn->prepare("SELECT object_id, count FROM items WHERE owner_id = ? AND item_id = ? AND loc = 'INVENTORY' FOR UPDATE");
        $stmt->bind_param("ii", $charId, $gameItemId);
        $stmt->execute();
        $stmt->bind_result($existingObjectId, $existingCount);
        $hasItem = $stmt->fetch();
        $stmt->close();

        if ($hasItem) {
            // Atualiza quantidade
            $newCount = $existingCount + $quantity;
            $stmt = $conn->prepare("UPDATE items SET count = ? WHERE object_id = ?");
            $stmt->bind_param("ii", $newCount, $existingObjectId);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar item stackable.");
            }
            $stmt->close();
        } else {
            // Insere novo item stackable
            $objectId = generateUniqueObjectId($conn);
            $stmt = $conn->prepare("INSERT INTO items 
                (owner_id, object_id, item_id, count, enchant_level, loc, loc_data, custom_type1, custom_type2, mana_left, time)
                VALUES (?, ?, ?, ?, 0, 'INVENTORY', 0, 0, 0, -1, -1)");
            $stmt->bind_param("iiii", $charId, $objectId, $gameItemId, $quantity);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao inserir item stackable.");
            }
            $stmt->close();
        }

    } else {
        // Insere item não empilhável, um por um
        for ($i = 0; $i < $quantity; $i++) {
            $objectId = generateUniqueObjectId($conn);
            $stmt = $conn->prepare("INSERT INTO items 
                (owner_id, object_id, item_id, count, enchant_level, loc, loc_data, custom_type1, custom_type2, mana_left, time)
                VALUES (?, ?, ?, 1, 0, 'INVENTORY', 0, 0, 0, -1, -1)");
            $stmt->bind_param("iii", $charId, $objectId, $gameItemId);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao inserir item não stackable.");
            }
            $stmt->close();
        }
    }

    // Deduz saldo
    $stmt = $conn->prepare("UPDATE account_balance SET balance = balance - ? WHERE login = ?");
    $stmt->bind_param("ds", $item['price'], $username);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar saldo.");
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Item entregue com sucesso.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro na compra: ' . $e->getMessage()]);
}
?>
