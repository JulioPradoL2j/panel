<?php
if (file_exists('./includes/config.php')) {
    header("Location: ./index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Instalação - Etapa 1</title>
<link rel="stylesheet" href="assets/style.css">
<link rel="shortcut icon" href="../icon/favicon.png">
<script>
function validateForm() {
    const host = document.forms["installForm"]["db_host"].value.trim();
    const pass = document.forms["installForm"]["db_pass"].value;
    const ipRegex = /^(localhost|(\d{1,3}\.){3}\d{1,3})$/;

    if (!ipRegex.test(host)) {
        alert("Host deve ser 'localhost' ou um endereço IP válido (ex: 127.0.0.1)");
        return false;
    }
    if (pass.length < 4) {
        alert("Senha deve ter pelo menos 4 caracteres.");
        return false;
    }
    return true;
}
</script>
</head>
<body class="dark">
<div class="install-container">
    <h1>Configuração Inicial do Banco de Dados</h1>
    <form name="installForm" method="post" action="step2.php" onsubmit="return validateForm();">
        <label for="db_host">Host:</label>
        <input type="text" id="db_host" name="db_host" required placeholder="localhost ou 127.0.0.1">

        <label for="db_name">Nome do Banco:</label>
        <input type="text" id="db_name" name="db_name" required placeholder="l2jdb">

        <label for="db_user">Usuário:</label>
        <input type="text" id="db_user" name="db_user" required placeholder="root">

        <label for="db_pass">Senha:</label>
        <input type="password" id="db_pass" name="db_pass" required placeholder="Mínimo 4 caracteres">

        <label for="project">Projeto:</label>
        <select id="project" name="project">
            <option value="L2JDreamV2">L2JDreamV2</option>
        </select>

        <label for="chronicle">Chronicle:</label>
        <select id="chronicle" name="chronicle">
            <option value="interlude">Interlude</option>
        </select>

        <button type="submit">Próximo</button>
    </form>
</div>
</body>
</html>
