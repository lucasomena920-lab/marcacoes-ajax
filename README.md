# Marcações Online (PHP + AJAX)

*[English version](README.en.md)*

Sistema de marcações para um salão de cabeleireiro fictício, o "Salão Aurora". O cliente escolhe o serviço, o dia e a hora, e o salão vê as marcações num painel próprio.

A ideia era fazer uma página que funcionasse toda sem recarregar, usando `fetch` (AJAX) para falar com uma pequena API em PHP que devolve JSON.

![Página de marcação](docs/screenshot-marcar.png)

## Como funciona

**Para o cliente**

1. Escolhe um serviço (cada um tem duração e preço)
2. Escolhe o dia e aparecem só as horas livres, calculadas no servidor
3. Preenche nome, email e telemóvel e confirma

**Para o salão** (`/admin/`)

- Login com utilizador e password
- Lista das marcações por dia, com navegação entre dias
- Marcar como concluída, cancelar ou repor uma marcação
- Total previsto do dia

As horas livres têm em conta o horário do salão (com pausa de almoço, sábado só de manhã, domingo fechado), a duração do serviço e as marcações que já existem. Tudo isso está configurado em `src/config.php`.

![Painel do salão](docs/screenshot-admin.png)

## Tecnologias

- PHP 8.1+ sem framework
- MySQL / MariaDB (PDO)
- JavaScript "vanilla" com `fetch` e `async/await`
- HTML e CSS (sem Bootstrap)

## API

| Método | Endpoint | Para quê |
|---|---|---|
| GET | `/api/servicos.php` | lista de serviços ativos |
| GET | `/api/horarios.php?servico=1&data=2026-11-20` | horas livres desse dia |
| POST | `/api/marcacoes.php` | criar marcação (JSON) |
| POST | `/api/admin/login.php` | login do salão |
| POST | `/api/admin/logout.php` | sair |
| GET | `/api/admin/marcacoes.php?data=...` | marcações do dia (precisa de login) |
| POST | `/api/admin/marcacoes.php` | mudar estado de uma marcação |

As respostas são sempre JSON. Os erros vêm com o código HTTP certo (422 para validação, 409 quando o horário já foi ocupado, 429 quando há pedidos a mais, etc.).

## Segurança

Algumas coisas em que pensei ao fazer isto:

- **Validação no servidor** – o JavaScript ajuda o utilizador, mas quem decide é o PHP. Se alguém mandar uma hora que não está na lista de horas livres (ou um serviço que não existe), o pedido é recusado.
- **Marcações em duplicado** – se duas pessoas tentarem a mesma hora ao mesmo tempo, só uma consegue. Uso `GET_LOCK()` do MySQL à volta da verificação + inserção, para não haver *race conditions*.
- **SQL injection** – só prepared statements.
- **XSS** – no front-end os dados são sempre inseridos com `textContent` / `createElement`, nunca com `innerHTML`. Há também um Content-Security-Policy sem scripts inline.
- **CSRF** – os pedidos `POST` levam o token no cabeçalho `X-CSRF-Token`. A API também só aceita `Content-Type: application/json`, o que impede formulários de outros sites de submeterem pedidos.
- **Login do admin** – password com `password_hash`, id de sessão regenerado depois do login, cookie `HttpOnly` e `SameSite=Strict`, bloqueio de 15 minutos depois de 5 tentativas erradas.
- **Spam** – limite de 5 marcações por hora por IP e um campo "honeypot" escondido para apanhar bots.
- **Sem password por defeito** – não existe nenhum admin criado no SQL. Cria-se pela linha de comandos (ver em baixo).

## Instalação

Precisas de PHP 8.1+ e MySQL/MariaDB.

```bash
git clone https://github.com/lucasomena920-lab/marcacoes-ajax.git
cd marcacoes-ajax

mysql -u root -p < database/schema.sql
cp .env.example .env        # e muda os dados da base de dados

# criar o utilizador do salão (pede a password no terminal)
php bin/criar-admin.php o_teu_utilizador

php -S localhost:8000 -t public
```

Abre `http://localhost:8000` para marcar e `http://localhost:8000/admin/` para o painel.

## Estrutura

```
public/
  index.php            -> página de marcação
  admin/index.php      -> painel do salão
  api/                 -> endpoints JSON
  assets/js/api.js     -> wrapper do fetch (CSRF, erros)
  assets/js/marcar.js  -> lógica da página de marcação
  assets/js/admin.js   -> lógica do painel
src/
  Agenda.php           -> cálculo das horas livres e gravação das marcações
  RateLimiter.php      -> limite de pedidos por IP
  config.php           -> horário do salão e regras
bin/criar-admin.php    -> criar/alterar admin
database/schema.sql
```

## Ideias para continuar

- Enviar email de confirmação ao cliente (PHPMailer)
- Link para o cliente cancelar a marcação sozinho
- Vários profissionais, cada um com a sua agenda
- Bloquear dias de férias/feriados a partir do painel

## Licença

MIT
