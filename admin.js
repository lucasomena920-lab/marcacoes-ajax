const STATUS_LABELS = {
  confirmada: 'Confirmada',
  concluida: 'Concluída',
  cancelada: 'Cancelada',
};

// ---------- login ----------

const loginForm = document.getElementById('login-form');

if (loginForm) {
  const loginMessage = document.getElementById('login-message');

  loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    loginMessage.hidden = true;

    try {
      await Api.post('/api/admin/login.php', {
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
      });
      window.location.reload();
    } catch (error) {
      loginMessage.textContent = error.message;
      loginMessage.hidden = false;
    }
  });
}

// ---------- painel ----------

const dateInput = document.getElementById('admin-date');

if (dateInput) {
  const tbody = document.getElementById('bookings-body');
  const summary = document.getElementById('day-summary');

  async function loadDay() {
    tbody.replaceChildren(row('A carregar...'));
    summary.textContent = '';

    try {
      const { bookings } = await Api.get(`/api/admin/marcacoes.php?data=${encodeURIComponent(dateInput.value)}`);
      renderBookings(bookings);
    } catch (error) {
      if (error.status === 401) return window.location.reload();
      tbody.replaceChildren(row(error.message));
    }
  }

  function row(text) {
    const tr = el('tr');
    tr.append(el('td', { text, attrs: { colspan: '5' }, className: 'muted' }));
    return tr;
  }

  function renderBookings(bookings) {
    if (bookings.length === 0) {
      tbody.replaceChildren(row('Sem marcações neste dia.'));
      return;
    }

    const active = bookings.filter((b) => b.status !== 'cancelada');
    const total = active.reduce((sum, b) => sum + Number(b.price_cents), 0);
    summary.textContent = `${active.length} marcação(ões) · previsto ${euros(total)}`;

    tbody.replaceChildren(...bookings.map((b) => {
      const tr = el('tr', { className: `status-${b.status}` });

      const client = el('td');
      client.append(el('strong', { text: b.client_name }), el('br'));
      client.append(el('a', { text: b.client_phone, attrs: { href: `tel:${b.client_phone}` } }));
      client.append(el('span', { className: 'muted', text: ` · ${b.client_email}` }));
      if (b.notes) client.append(el('div', { className: 'notes', text: b.notes }));

      const actions = el('td', { className: 'actions' });
      if (b.status === 'confirmada') {
        actions.append(
          el('button', { className: 'btn btn--small', text: 'Concluída', attrs: { 'data-id': b.id, 'data-status': 'concluida' } }),
          el('button', { className: 'btn btn--small btn--danger', text: 'Cancelar', attrs: { 'data-id': b.id, 'data-status': 'cancelada' } }),
        );
      } else {
        actions.append(el('button', { className: 'btn btn--small btn--ghost', text: 'Repor', attrs: { 'data-id': b.id, 'data-status': 'confirmada' } }));
      }

      tr.append(
        el('td', { text: `${b.start_time}–${b.end_time}` }),
        client,
        el('td', { text: b.service_name }),
        el('td', { text: STATUS_LABELS[b.status] ?? b.status }),
        actions,
      );
      return tr;
    }));
  }

  tbody.addEventListener('click', async (event) => {
    const button = event.target.closest('button[data-id]');
    if (!button) return;

    if (button.dataset.status === 'cancelada' && !window.confirm('Cancelar esta marcação?')) {
      return;
    }

    button.disabled = true;
    try {
      await Api.post('/api/admin/marcacoes.php', {
        id: Number(button.dataset.id),
        estado: button.dataset.status,
      });
      loadDay();
    } catch (error) {
      alert(error.message);
      button.disabled = false;
    }
  });

  function shiftDay(days) {
    const date = new Date(`${dateInput.value}T12:00:00`);
    date.setDate(date.getDate() + days);
    dateInput.value = date.toISOString().slice(0, 10);
    loadDay();
  }

  document.querySelectorAll('[data-shift]').forEach((btn) => {
    btn.addEventListener('click', () => shiftDay(Number(btn.dataset.shift)));
  });

  document.getElementById('today-btn').addEventListener('click', () => {
    dateInput.value = new Date().toLocaleDateString('sv-SE'); // formato AAAA-MM-DD
    loadDay();
  });

  dateInput.addEventListener('change', loadDay);

  document.getElementById('logout').addEventListener('click', async () => {
    await Api.post('/api/admin/logout.php', {}).catch(() => {});
    window.location.reload();
  });

  loadDay();
}
