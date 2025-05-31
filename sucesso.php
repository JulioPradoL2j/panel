<?php
session_start();
$config = require_once 'includes/config.php';
// Proteção de acesso
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Verifica se o session_id foi passado
if (!isset($_GET['session_id'])) {
    echo "ID da sessão de pagamento não informado.";
    exit;
}

require_once 'stripe-php-6.43.1/init.php'; // Caminho correto do seu Stripe SDK


  \Stripe\Stripe::setApiKey($config['stripe_private_key'] ?? '');

try {
    $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
    $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);

    $charName = $session->metadata->char_name ?? 'Desconhecido';
    $email    = $session->customer_details->email ?? 'Não informado';
    $amount   = number_format($session->amount_total / 100, 2, ',', '.');
    $status   = $paymentIntent->status;

} catch (Exception $e) {
    echo "Erro ao buscar dados do pagamento: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pagamento Realizado com Sucesso</title>
    <style>
        body {
            background-color: #121212;
            color: #fff;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }
        .box {
            background-color: #1e1e1e;
            padding: 30px;
            border-radius: 12px;
            display: inline-block;
            box-shadow: 0 0 10px #333;
        }
        h1 {
            color: #00c853;
        }
    </style>
</head>
<body>

<div class="box">
    <h1>✅ Pagamento Aprovado!</h1>
    <p><strong>Personagem:</strong> <?= htmlspecialchars($charName) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
    <p><strong>Valor:</strong> R$ <?= $amount ?></p>
    <p><strong>Status:</strong> <?= ucfirst($status) ?></p>
</div>

</body>
</html>
