<?php
session_start();
require_once 'ind/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

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

$conn = getDbConnection();
$username = $_SESSION['username'];
$oldName = $_POST['old_char_name'] ?? '';
$newName = $_POST['new_char_name'] ?? '';
$nomeValido = preg_match('/^[a-zA-Z0-9_]{3,16}$/', $newName);
$mensagem = '';
$tipo = 'erro';

if ($oldName && $newName && $nomeValido) {
    // Verifica se personagem está online
    $stmtOnline = $conn->prepare("SELECT online FROM characters WHERE char_name = ? AND account_name = ?");
    $stmtOnline->bind_param("ss", $oldName, $username);
    $stmtOnline->execute();
    $stmtOnline->bind_result($online);
    $stmtOnline->fetch();
    $stmtOnline->close();

    if ($online == 1) {
        $mensagem = $lang['character_online'];
    } else {
        // Verifica o saldo
        $stmtSaldo = $conn->prepare("SELECT balance FROM account_balance WHERE login = ?");
        $stmtSaldo->bind_param("s", $username);
        $stmtSaldo->execute();
        $stmtSaldo->bind_result($saldo);
        $stmtSaldo->fetch();
        $stmtSaldo->close();

        if ($saldo >= 5) {
            $stmt = $conn->prepare("UPDATE characters SET char_name = ? WHERE char_name = ? AND account_name = ?");
            $stmt->bind_param("sss", $newName, $oldName, $username);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $stmt2 = $conn->prepare("UPDATE account_balance SET balance = balance - 5 WHERE login = ?");
                $stmt2->bind_param("s", $username);
                $stmt2->execute();
                $stmt2->close();

                $mensagem = $lang['change_name_success'];
                $tipo = "sucesso";
            } else {
                $mensagem = $lang['change_name_error'];
            }

            $stmt->close();
        } else {
            $mensagem = $lang['insufficient_balance'];
        }
    }
} else {
    $mensagem = $lang['invalid_name'];
}


// Redireciona com mensagem via GET
header("Location: services.php?msg=" . urlencode($mensagem) . "&tipo=" . $tipo);
exit;
