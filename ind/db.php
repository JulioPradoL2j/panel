<?php
// www/ind/db.php

// Função para obter a conexão com o banco de dados
function getDbConnection() {
    // Caminho para o arquivo de configuração
    $configPath = __DIR__ . '/../includes/config.php';

    if (!file_exists($configPath)) {
        throw new \Exception('Arquivo de configuração não encontrado em: ' . $configPath);
    }

    // Carrega as configurações
    $config = require $configPath;

    // Valida se as chaves exitem
    $required = ['db_host', 'db_name', 'db_user', 'db_pass'];
    foreach ($required as $key) {
        if (!isset($config[$key])) {
            throw new \Exception("Chave de configuração ausente: $key");
        }
    }

    // Cria a conexão mysqli
    $conn = new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_pass'],
        $config['db_name']
    );

    // Verifica erros de conexão
    if ($conn->connect_error) {
        throw new \Exception('Erro na conexão com o banco de dados: ' . $conn->connect_error);
    }

    // Ajusta charset para UTF-8
    $conn->set_charset('utf8mb4');

    return $conn;
}




// Exemplo de uso:
// $db = getDbConnection();
// $result = $db->query("SELECT * FROM users");
// while ($row = $result->fetch_assoc()) {
//     var_dump($row);
// }
?>