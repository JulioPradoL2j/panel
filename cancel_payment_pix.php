<?php
require 'ind/db.php'; // ajuste o caminho conforme necessário
ini_set('display_errors', 1);
$config = require_once 'includes/config.php';

$accessToken = $config['mercadopago_private_key'] ?? '';

$conn = getDbConnection();
$paymentId = $_GET['payment_id'] ?? null;
if (!$paymentId) {
    echo json_encode(['success' => false, 'error' => 'ID ausente.']);
    exit;
}

// Verifica se já foi aprovado ou processado
$stmt = $conn->prepare("SELECT status, processed FROM pix_payments WHERE payment_id = ?");
$stmt->bind_param("s", $paymentId);
$stmt->execute();
$stmt->bind_result($status, $processed);
$stmt->fetch();
$stmt->close();

if ($status !== 'pending') {
    echo json_encode(['success' => false, 'error' => 'Status inválido para cancelamento.']);
    exit;
}



// Cancela via API MercadoPago
$url = "https://api.mercadopago.com/v1/payments/$paymentId";
$headers = [
    "Authorization: Bearer $accessToken",
    "Content-Type: application/json"
];

$data = ['status' => 'cancelled'];

  console.log("Resposta do cancelamento:", data); // ← Aqui
  
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    // Atualiza no banco
    $stmt = $conn->prepare("UPDATE pix_payments SET status = 'cancelled' WHERE payment_id = ?");
    $stmt->bind_param("s", $paymentId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Falha ao cancelar via API']);
}
?>
