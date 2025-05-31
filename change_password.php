<?php
session_start();
require_once 'includes/captcha.php';
require_once 'ind/db.php';

// Carrega o idioma ativo
$langCode = $_SESSION['lang'] ?? 'pt';
$langFile = "lang/$langCode.php";
if (file_exists($langFile)) {
    $lang = include $langFile;
} else {
    $lang = include 'lang/pt.php';
}

$captcha = new Captcha();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$username = $_SESSION['username'] ?? null;
$oldPassword = $_POST['old_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$userCaptcha = $_POST['captcha'] ?? '';

// Validações básicas
if (strlen($newPassword) < 8 || $newPassword !== $confirmPassword) {
    $_SESSION['password_change_error'] = $lang['password_mismatch'];
    header("Location: settings.php");
    exit;
}

if (!$captcha->validateCaptcha($userCaptcha)) {
    $_SESSION['password_change_error'] = $lang['captcha_invalid'];
    header("Location: settings.php");
    exit;
}

$conn = getDbConnection();
$encodedOld = base64_encode(sha1($oldPassword, true));
$stmt = $conn->prepare("SELECT * FROM accounts WHERE login = ? AND password = ?");
$stmt->bind_param("ss", $username, $encodedOld);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['password_change_error'] = $lang['incorrect_current_password'];
    header("Location: settings.php");
    exit;
}

$newEncoded = base64_encode(sha1($newPassword, true));
$update = $conn->prepare("UPDATE accounts SET password = ? WHERE login = ?");
$update->bind_param("ss", $newEncoded, $username);
$update->execute();

$_SESSION['password_change_success'] = $lang['password_changed_success'];
header("Location: settings.php");
exit;
