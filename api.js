// Pequeno wrapper à volta do fetch para falar com a API em JSON.
// Envia sempre o token CSRF que vem na <meta> da página.

const Api = (() => {
  let csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  async function request(url, { method = 'GET', body } = {}) {
    const options = {
      method,
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    };

    if (body !== undefined) {
      options.headers['Content-Type'] = 'application/json';
      options.headers['X-CSRF-Token'] = csrf;
      options.body = JSON.stringify(body);
    }

    let response;
    try {
      response = await fetch(url, options);
    } catch {
      throw new ApiError('Sem ligação ao servidor. Verifica a internet.', 0);
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new ApiError(data.error || 'Algo correu mal.', response.status, data.fields);
    }

    return data;
  }

  class ApiError extends Error {
    constructor(message, status, fields = {}) {
      super(message);
      this.status = status;
      this.fields = fields || {};
    }
  }

  return {
    get: (url) => request(url),
    post: (url, body) => request(url, { method: 'POST', body }),
    setCsrf: (token) => { csrf = token; },
    ApiError,
  };
})();

// Cria um elemento com texto (nunca innerHTML com dados vindos do servidor)
function el(tag, { className, text, attrs } = {}) {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text !== undefined) node.textContent = text;
  if (attrs) Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
  return node;
}

function euros(cents) {
  return (cents / 100).toLocaleString('pt-PT', { style: 'currency', currency: 'EUR' });
}
