🌐 Painel UCP - Sistema Completo e Moderno para L2J
Este projeto oferece um Painel de Controle de Usuário (UCP) completo e moderno para servidores Lineage 2 baseados em L2J. Desenvolvido com foco na segurança, desempenho e experiência do usuário, o painel integra funcionalidades essenciais para a gestão de contas e interações dos jogadores.
maxcheaters.com

🚀 Funcionalidades Principais
Registro e Login de Usuários: Sistema seguro com validação de captchas para prevenir bots e acessos não autorizados.

Geração de Arquivo XML: Após o registro, o sistema gera automaticamente um arquivo XML contendo as informações da conta, permitindo que o usuário salve seus dados localmente.

Redirecionamento Pós-Registro: Após o registro bem-sucedido, o usuário é redirecionado para a página de login (index.php) após 15 segundos.

Integração com Tabela de Saldo: Ao criar uma nova conta, o sistema insere automaticamente um registro correspondente na tabela account_balance, inicializando o saldo do usuário.

Interface Responsiva: Design moderno e responsivo, garantindo uma experiência consistente em diferentes dispositivos.

🛠️ Tecnologias Utilizadas
PHP: Linguagem principal para o desenvolvimento do backend.

MySQL: Banco de dados relacional para armazenamento de informações de contas e saldos.

HTML5 & CSS3: Estrutura e estilização das páginas web.

JavaScript: Funcionalidades interativas, como redirecionamento e geração de arquivos XML.

📂 Estrutura do Projeto
ind/db.php: Arquivo de conexão com o banco de dados.

includes/captcha.php: Classe para geração e validação de captchas.

register.php: Página de registro de novos usuários.

index.php: Página de login dos usuários.

templates/dark/assets/: Arquivos de estilo e imagens utilizados no frontend.
L2 OldSkool C3

📄 Requisitos
Servidor web com suporte a PHP 7.4 ou superior.

Banco de dados MySQL 5.7 ou superior.

Extensões PHP: mysqli, dom, session.

🔧 Instalação
Clone o repositório:

bash
Copiar
Editar
git clone https://github.com/seuusuario/painel-ucp.git
Configure o banco de dados:

Importe as tabelas necessárias (accounts, account_balance) no seu banco de dados MySQL.

Atualize as credenciais de conexão no arquivo ind/db.php.

Certifique-se de que as permissões de escrita estão corretas para a geração e download dos arquivos XML.

Acesse register.php para criar uma nova conta e testar o sistema.

✅ Contribuições
Contribuições são bem-vindas! Sinta-se à vontade para abrir issues ou enviar pull requests com melhorias, correções ou novas funcionalidades.
