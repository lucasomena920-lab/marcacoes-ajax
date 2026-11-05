const form = document.getElementById('booking-form');
const servicesBox = document.getElementById('services');
const slotsBox = document.getElementById('slots');
const dateInput = document.getElementById('date');
const message = document.getElementById('form-message');
const submitBtn = document.getElementById('submit-btn');
const successBox = document.getElementById('success');

const state = {
  serviceId: null,
  time: null,
};

// ---------- serviços ----------

async function loadServices() {
  try {
    const { services } = await Api.get('/api/servicos.php');
    servicesBox.replaceChildren();

    services.forEach((service) => {
      const label = el('label', { className: 'service' });
      const radio = el('input', { attrs: { type: 'radio', name: 'servico', value: service.id } });
      const info = el('span', { className: 'service__info' });

      info.append(
        el('strong', { text: service.name }),
        el('small', { text: `${service.duration_min} min` }),
      );

      label.append(radio, info, el('span', { className: 'service__price', text: euros(service.price_cents) }));
      servicesBox.append(label);
    });
  } catch (error) {
    servicesBox.replaceChildren(el('p', { className: 'error', text: error.message }));
  }
}

servicesBox.addEventListener('change', (event) => {
  if (event.target.name !== 'servico') return;
  state.serviceId = Number(event.target.value);
  loadSlots();
});

// ---------- horários ----------

let slotsRequest = 0;

async function loadSlots() {
  state.time = null;

  if (!state.serviceId || !dateInput.value) {
    return;
  }

  // se o utilizador mudar de dia rápido, ignora respostas antigas
  const requestId = ++slotsRequest;
  slotsBox.replaceChildren(el('p', { className: 'muted', text: 'A procurar horários livres...' }));

  try {
    const params = new URLSearchParams({ servico: state.serviceId, data: dateInput.value });
    const { slots } = await Api.get(`/api/horarios.php?${params}`);

    if (requestId !== slotsRequest) return;

    if (slots.length === 0) {
      slotsBox.replaceChildren(el('p', { className: 'muted', text: 'Não há horários livres neste dia. Experimenta outro.' }));
      return;
    }

    const list = el('div', { className: 'slots__list' });
    slots.forEach((time) => {
      list.append(el('button', { className: 'slot', text: time, attrs: { type: 'button', 'data-time': time } }));
    });
    slotsBox.replaceChildren(list);
  } catch (error) {
    if (requestId !== slotsRequest) return;
    slotsBox.replaceChildren(el('p', { className: 'error', text: error.message }));
  }
}

dateInput.addEventListener('change', loadSlots);

slotsBox.addEventListener('click', (event) => {
  const button = event.target.closest('.slot');
  if (!button) return;

  slotsBox.querySelectorAll('.slot').forEach((b) => b.classList.remove('slot--active'));
  button.classList.add('slot--active');
  state.time = button.dataset.time;
});

// ---------- envio ----------

function showMessage(text) {
  message.textContent = text;
  message.hidden = !text;
}

function showFieldErrors(fields = {}) {
  form.querySelectorAll('[data-error-for]').forEach((small) => {
    small.textContent = fields[small.dataset.errorFor] || '';
  });
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  showMessage('');
  showFieldErrors();

  if (!state.serviceId) return showMessage('Escolhe um serviço.');
  if (!state.time) return showMessage('Escolhe um dia e uma hora.');

  const data = new FormData(form);
  const payload = {
    servico: state.serviceId,
    data: dateInput.value,
    hora: state.time,
    nome: data.get('nome'),
    email: data.get('email'),
    telefone: data.get('telefone'),
    notas: data.get('notas'),
    website: data.get('website'),
  };

  submitBtn.disabled = true;
  submitBtn.textContent = 'A marcar...';

  try {
    const { booking } = await Api.post('/api/marcacoes.php', payload);

    document.getElementById('success-text').textContent =
      `${booking.service} no dia ${booking.date} às ${booking.time}.`;
    form.hidden = true;
    successBox.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (error) {
    showMessage(error.message);
    showFieldErrors(error.fields);

    // o horário foi ocupado entretanto: recarrega a lista
    if (error.status === 409) loadSlots();
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Confirmar marcação';
  }
});

document.getElementById('new-booking').addEventListener('click', () => {
  form.reset();
  state.serviceId = null;
  state.time = null;
  slotsBox.replaceChildren(el('p', { className: 'muted', text: 'Escolhe primeiro um serviço e um dia.' }));
  successBox.hidden = true;
  form.hidden = false;
});

loadServices();
