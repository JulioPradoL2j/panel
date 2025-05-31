<?php
require_once 'ind/db.php';

function loginUser($username, $password) {
    $conn = getDbConnection();
    
    // IMPORTANTE: o segundo parâmetro `true` gera a saída binária esperada
    $encodedPass = base64_encode(sha1($password, true));

    $stmt = $conn->prepare("SELECT * FROM accounts WHERE login = ? AND password = ?");
    if (!$stmt) {
        die("Erro na preparação da query: " . $conn->error);
    }

    $stmt->bind_param("ss", $username, $encodedPass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;
        return true;
    }

    return false;
}