<?php
session_start();
require_once 'ind/db.php';
require_once 'stripe-php-6.43.1/init.php';
$config = require_once 'includes/config.php';

$urlsite = $config['site_url'] ?? '';


if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$conn = getDbConnection();
if ($conn->connect_error) {
    echo json_encode(['error' => 'Erro ao conectar ao banco de dados: ' . $conn->connect_error]);
    exit;
}

// Coleta os dados do POST
$charName = $_POST['char_name'] ?? null;
$amount   = (float)($_POST['amount'] ?? 0);
$method   = $_POST['payment_method'] ?? null;

if (!$charName || $amount <= 0 || !$method) {
    echo json_encode(['error' => 'Dados incompletos enviados.']);
    exit;
}

//
// ==========================
// MÉTODO MERCADO PAGO (PIX)
// ==========================
if ($method === 'MercadoPago' && ($config['use_mercadopago'] ?? false)) {
    // Gera email anônimo único
    $email = "anon_" . uniqid() . "@anonymous.com";

    // Dados para o Mercado Pago
    $paymentData = [
        "transaction_amount" => $amount,
        "description" => "Doação para personagem $charName",
        "payment_method_id" => "pix",
        "payer" => [
            "email" => $email,
            "first_name" => "Anonymous",
            "last_name" => "User",
        ],
        "external_reference" => "donate",
        "installments" => 1,
        "binary_mode" => true
    ];

    $access_token = $config['mercadopago_private_key'] ?? '';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "Authorization: Bearer $access_token",
            "X-Idempotency-Key: " . uniqid('pix_', true)
        ],
        CURLOPT_POSTFIELDS => json_encode($paymentData)
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $data = json_decode($response, true);

    if ($http_code !== 201 || !isset($data['id'])) {
        echo json_encode([
            'error' => 'Erro ao criar pagamento no MercadoPago.',
            'http_code' => $http_code,
            'details' => $data
        ]);
        exit;
    }

    $payment_id  = $data['id'];
    $qr_code     = $data['point_of_interaction']['transaction_data']['qr_code'] ?? null;
    $qr_base64   = $data['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

    // Salva no banco
    try {
        $stmt = $conn->prepare("INSERT INTO pix_payments (char_name, payment_id, qr_code, qr_code_base64) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $charName, $payment_id, $qr_code, $qr_base64);

        if (!$stmt->execute()) {
            echo json_encode(['error' => 'Erro ao salvar no banco: ' . $stmt->error]);
            exit;
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro ao salvar no banco: ' . $e->getMessage()]);
        exit;
    }

    // Redireciona para a página de pagamento PIX
    header("Location: checkout.php?payment_id=" . urlencode($payment_id));
    exit;
}

// ==========================
// MÉTODO STRIPE
// ==========================
else if ($method === 'Stripe' && ($config['use_stripe'] ?? false)) {

    // Inicializa Stripe com a chave secreta
   \Stripe\Stripe::setApiKey($config['stripe_private_key'] ?? '');

    try {
		$session = \Stripe\Checkout\Session::create([
			'payment_method_types' => ['card'],
			'line_items' => [[
				'price_data' => [
					'currency' => 'brl',
					'product_data' => [
						'name' => 'Doação para personagem ' . $charName,
					],
					'unit_amount' => intval($amount * 100), // em centavos
				],
				'quantity' => 1,
			]],
			'mode' => 'payment',
			'success_url' => $urlsite . '/sucesso.php?session_id={CHECKOUT_SESSION_ID}',
			'cancel_url' => $urlsite . '/cancelado.php',
			'metadata' => [
				'char_name' => $charName,
				'user_id' => $_SESSION['user_id'] ?? 'unknown'
			]
		]);



        // Redireciona para o Stripe Checkout
        header("Location: " . $session->url);
        exit;

    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro ao criar sessão Stripe: ' . $e->getMessage()]);
        exit;
    }
}


//
// ==========================
// MÉTODO INVÁLIDO
// ==========================
else {
    echo json_encode(['error' => 'Método de pagamento inválido']);
    exit;
}
