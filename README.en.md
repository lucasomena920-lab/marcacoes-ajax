# Online Booking (PHP + AJAX)

*[Versão em português](README.md)*

A booking system for a made-up hair salon, "Salão Aurora". Customers pick a service, a day and a time, and the salon sees the bookings in its own dashboard.

The goal was to build a page that works without reloading, using `fetch` (AJAX) to talk to a small PHP API that returns JSON.

![Booking page](docs/screenshot-marcar.png)

## How it works

**For customers**

1. Pick a service (each one has a duration and a price)
2. Pick a day and only the free times show up, calculated on the server
3. Fill in name, email and phone number and confirm

**For the salon** (`/admin/`)

- Login with username and password
- Bookings listed by day, with day-to-day navigation
- Mark a booking as done, cancel it or restore it
- Expected total for the day

Free times take into account the opening hours (lunch break, Saturday mornings only, closed on Sunday), the length of the service and existing bookings. All of that lives in `src/config.php`.

![Salon dashboard](docs/screenshot-admin.png)

## Stack

- PHP 8.1+, no framework
- MySQL / MariaDB (PDO)
- Vanilla JavaScript with `fetch` and `async/await`
- HTML and CSS (no Bootstrap)

## API

| Method | Endpoint | What it does |
|---|---|---|
| GET | `/api/servicos.php` | list active services |
| GET | `/api/horarios.php?servico=1&data=2026-11-20` | free times for that day |
| POST | `/api/marcacoes.php` | create a booking (JSON) |
| POST | `/api/admin/login.php` | salon login |
| POST | `/api/admin/logout.php` | log out |
| GET | `/api/admin/marcacoes.php?data=...` | bookings for a day (login required) |
| POST | `/api/admin/marcacoes.php` | change a booking's status |

Responses are always JSON, and errors use the proper HTTP status (422 for validation, 409 when the slot was already taken, 429 for too many requests, and so on).

## Security

Things I kept in mind while building it:

- **Server-side validation** – the JavaScript helps the user, but PHP has the final say. If someone sends a time that isn't in the free list (or a service that doesn't exist), the request is rejected.
- **Double bookings** – if two people try to book the same time at once, only one gets it. I use MySQL's `GET_LOCK()` around the check + insert to avoid race conditions.
- **SQL injection** – prepared statements only.
- **XSS** – on the front end, data is always inserted with `textContent` / `createElement`, never `innerHTML`. There's also a Content-Security-Policy with no inline scripts.
- **CSRF** – `POST` requests send the token in an `X-CSRF-Token` header. The API also only accepts `Content-Type: application/json`, which stops forms on other sites from posting to it.
- **Admin login** – `password_hash` for the password, session id regenerated after login, `HttpOnly` and `SameSite=Strict` cookie, 15-minute lockout after 5 wrong attempts.
- **Spam** – limit of 5 bookings per hour per IP, plus a hidden honeypot field to catch bots.
- **No default password** – there's no admin user in the SQL file. You create one from the command line (see below).

## Setup

You need PHP 8.1+ and MySQL/MariaDB.

```bash
git clone https://github.com/lucasomena920-lab/marcacoes-ajax.git
cd marcacoes-ajax

mysql -u root -p < database/schema.sql
cp .env.example .env        # then edit the database settings

# create the salon user (asks for the password in the terminal)
php bin/criar-admin.php your_username

php -S localhost:8000 -t public
```

Open `http://localhost:8000` to book and `http://localhost:8000/admin/` for the dashboard.

## Structure

```
public/
  index.php            -> booking page
  admin/index.php      -> salon dashboard
  api/                 -> JSON endpoints
  assets/js/api.js     -> fetch wrapper (CSRF, errors)
  assets/js/marcar.js  -> booking page logic
  assets/js/admin.js   -> dashboard logic
src/
  Agenda.php           -> free-time calculation and saving bookings
  RateLimiter.php      -> per-IP request limits
  config.php           -> opening hours and rules
bin/criar-admin.php    -> create/update an admin
database/schema.sql
```

The code and UI are in Portuguese since the project is aimed at a Portuguese business.

## Ideas for later

- Confirmation email to the customer (PHPMailer)
- A link so customers can cancel on their own
- Several staff members, each with their own schedule
- Blocking holidays/days off from the dashboard

## License

MIT
