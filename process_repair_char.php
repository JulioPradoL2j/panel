<?php
session_start();
require_once 'ind/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$lang = include "lang/{$_SESSION['lang']}.php";
$username = $_SESSION['username'];
$charName = $_POST['repair_char_name'] ?? '';
$mensagem = '';
$tipo = 'erro';

// Coordenadas fixas de Giran
$giran_x = 82698;
$giran_y = 148638;
$giran_z = -3469;

if ($charName) {
    $conn = getDbConnection();

    // Verifica se o personagem pertence ao usuário e está offline
    $stmt = $conn->prepare("SELECT online FROM characters WHERE char_name = ? AND account_name = ?");
    $stmt->bind_param("ss", $charName, $username);
    $stmt->execute();
    $stmt->bind_result($online);
    $stmt->fetch();
    $stmt->close();

    if ($online === 0) {
        // Atualiza as coordenadas do personagem para Giran
        $stmt2 = $conn->prepare("UPDATE characters SET x = ?, y = ?, z = ? WHERE char_name = ? AND account_name = ?");
        $stmt2->bind_param("iiiss", $giran_x, $giran_y, $giran_z, $charName, $username);
        $stmt2->execute();

        if ($stmt2->affected_rows > 0) {
            $mensagem = $lang['repair_success'] ?? 'Personagem reparado com sucesso!';
            $tipo = 'sucesso';
        } else {
            $mensagem = $lang['repair_failed'] ?? 'Erro ao tentar reparar o personagem.';
        }

        $stmt2->close();
    } else {
        $mensagem = $lang['character_online'] ?? 'O personagem está online. Deslogue para continuar.';
    }
} else {
    $mensagem = $lang['invalid_name'] ?? 'Nome inválido.';
}

header("Location: services.php?msg=" . urlencode($mensagem) . "&tipo=" . $tipo);
exit;
