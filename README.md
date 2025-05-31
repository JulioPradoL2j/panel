# 🌐 Painel UCP - Sistema Completo e Moderno para L2J

Este projeto oferece um **Painel de Controle de Usuário (UCP)** completo e moderno para servidores **Lineage 2** baseados em **L2J**.  
Desenvolvido com foco na **segurança**, **desempenho** e **experiência do usuário**, o painel integra funcionalidades essenciais para a gestão de contas e interações dos jogadores.

---

## 🚀 Funcionalidades Principais

- **🧾 Registro e Login de Usuários**  
  Sistema seguro com validação de captchas para prevenir bots e acessos não autorizados.

- **📄 Geração de Arquivo XML**  
  Após o registro, o sistema gera automaticamente um arquivo XML contendo as informações da conta, permitindo que o usuário salve seus dados localmente.

- **🔁 Redirecionamento Pós-Registro**  
  Após o registro bem-sucedido, o usuário é redirecionado automaticamente para a página de login (`index.php`) após 15 segundos.

- **💰 Integração com Tabela de Saldo**  
  Ao criar uma nova conta, o sistema insere automaticamente um registro correspondente na tabela `account_balance`, inicializando o saldo do usuário.

- **📱 Interface Responsiva**  
  Design moderno e responsivo, garantindo uma experiência consistente em diferentes dispositivos (desktop, tablet e mobile).

---

## 🛠️ Tecnologias Utilizadas

- **PHP** – Linguagem principal para o desenvolvimento do backend.  
- **MySQL** – Banco de dados relacional para armazenamento de informações de contas e saldos.  
- **HTML5 & CSS3** – Estrutura e estilização das páginas web.  
- **JavaScript** – Funcionalidades interativas, como redirecionamento automático e download de arquivos.

---

## 📸 Imagens (Em breve)

![image](https://github.com/user-attachments/assets/37ddc2c9-4821-44da-8fde-66c0e21fcfe6)

---

Olá, comunidade!

Estou trazendo um Painel UCP (User Control Panel) moderno, completo e pronto para integração com seu servidor Lineage 2! Abaixo você confere um tutorial passo a passo de como instalar o sistema em poucos minutos.

## ⚙️ Requisitos
-Antes de iniciar, certifique-se de ter:
-PHP 7.4 ou superior
-Servidor web (Apache/Nginx)
-MySQL/MariaDB
-Acesso ao banco de dados do seu servidor L2
-Permissões de escrita na pasta do painel

---

## 📥 1. Upload dos Arquivos
- Faça upload dos arquivos do painel em seu host ou localhost (ex: www/painel/).
---
## 🚀 2. Iniciando a Instalação
- Acesse o caminho onde instalou o painel via navegador:
---
- http://seudominio.com/painel/install/
- Você verá a tela de Configuração Inicial do Banco de Dados, conforme a imagem abaixo:
![image](https://github.com/user-attachments/assets/a4c831ba-4f77-4e50-b3f4-cc78da14fb19)
- Preencha os campos:
- Host: localhost ou 127.0.0.1
- Nome do Banco: (ex: l2jdb)
- Usuário: (ex: root)
- Senha: senha do seu banco (mínimo 4 caracteres)
- Projeto: selecione o projeto (L2JDreamV2 ou outro compatível)
- Chronicle: selecione a crônica (ex: Interlude)
- Clique em "Próximo".
---
## 🌍 3. Configuração do Site
Na próxima etapa, você verá a tela de Configurações do Site:
![image](https://github.com/user-attachments/assets/f34078c1-31e3-4d06-b0ab-6e0bc9d8d3b9)
- Configure conforme sua necessidade:
- URL do Site: insira o link do seu site (ex: https://meusite.com)
- Tema: escolha entre tema Claro ou Escuro
- Usar Mercado Pago?: marque se desejar ativar
- Usar Stripe?: marque se deseja usar Stripe como meio de pagamento
- Doações:
- Valores de doação: insira os valores permitidos (ex: 0.5,1,5,10,20,50)

--- 
## Bônus:
- Ativar bônus?: marque para liberar bônus automáticos

- Finalize clicando em "Finalizar Instalação".
---
## ✅ Instalação Concluída
-Após clicar em “Finalizar Instalação”, o sistema criará o arquivo de configuração e você será redirecionado para a tela de login.

- Você pode agora acessar o painel com as credenciais que serão definidas automaticamente ou configuradas no banco de dados após o primeiro acesso.
---
## 🧩 Funcionalidades do Painel
-Multilinguagem (PT/EN)
- Integração com Stripe e Mercado Pago
- Sistema de doações com entrega automática de saldo
- Gerenciamento de personagens, contas, itens e admins
- Loja virtual com entrega automática no inventário
- Layout moderno com tema escuro ou claro
- Suporte a múltiplas crônicas e projetos (L2JDream, L2JServer etc.)
---
## 📌 Observações
- Após a instalação, o diretório /install pode ser removido ou renomeado por segurança.
- Verifique permissões de pasta para permitir escrita do arquivo config.php.
- Em caso de erro no banco, revise o host, usuário e senha inseridos.

## 📣 Compartilhe com outros admins! Este painel é ideal para quem quer oferecer uma interface moderna e funcional para os jogadores.

- Dúvidas, bugs ou sugestões? Comente aqui no tópico ou envie mensagem!


---

## 📬 Contato

Para dúvidas, sugestões ou colaborações, entre em contato através do juliopradrol2j@gmail.com

