<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'ind/db.php'; // esse arquivo deve definir corretamente a variável $conn
$config = require_once 'includes/config.php';

$accessToken = $config['mercadopago_private_key'] ?? '';

$conn = getDbConnection();

$paymentId = $_GET['payment_id'] ?? null;
if (!$paymentId) {
    echo json_encode(['status' => 'error', 'message' => 'ID ausente']);
    exit;
}

try {

    $url = "https://api.mercadopago.com/v1/payments/" . urlencode($paymentId);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Erro ao consultar Mercado Pago: $response");
    }

    $data = json_decode($response, true);
    if (!isset($data['status'])) {
        throw new Exception("Resposta inesperada da API do Mercado Pago.");
    }

    $status = $data['status'];


    $stmt = $conn->prepare("SELECT char_name, processed FROM pix_payments WHERE payment_id = ?");
    $stmt->bind_param("s", $paymentId);
    $stmt->execute();
    $stmt->bind_result($charName, $processed);
    $stmt->fetch();
    $stmt->close();

   
    $stmt = $conn->prepare("UPDATE pix_payments SET status = ? WHERE payment_id = ?");
    $stmt->bind_param("ss", $status, $paymentId);
    $stmt->execute();
    $stmt->close();

if ($status === 'approved' && !$processed) {

    $stmt = $conn->prepare("SELECT account_name FROM characters WHERE char_name = ?");
    $stmt->bind_param("s", $charName);
    $stmt->execute();
    $stmt->bind_result($accountName);
    $stmt->fetch();
    $stmt->close();

    if (!$accountName) {
        throw new Exception("Conta não encontrada para o personagem: $charName");
    }

		$amount = $data['transaction_amount'] ?? 0.00;
		$saldo = $amount;
		
		if ($config['use_bonus'] && $amount >= $config['bonus_threshold']) {
			$bonus = $amount * ($config['bonus_percent'] / 100);
			$saldo += $bonus;
		}


    $stmt = $conn->prepare("INSERT INTO account_balance (login, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)");
    $stmt->bind_param("sd", $accountName, $saldo);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE pix_payments SET processed = 1 WHERE payment_id = ?");
    $stmt->bind_param("s", $paymentId);
    $stmt->execute();
    $stmt->close();
}


    echo json_encode([
        'status' => $status,
        'processed' => $status === 'approved'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
