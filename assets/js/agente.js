(function () {
  const caixa   = document.getElementById('agente-chat');
  const form    = document.getElementById('agente-form');
  const input   = document.getElementById('agente-input');
  const fazenda = window.AGENTE_FAZENDA_ID || 
                  (document.getElementById('agente-fazenda')?.value ?? 0);
  const BASE    = window.AGENTE_BASE_URL || '/';

  function bolha(texto, quem) {
    const div = document.createElement('div');
    div.className = 'agente-msg ' + quem;
    div.textContent = texto;
    caixa.appendChild(div);
    caixa.scrollTop = caixa.scrollHeight;
    return div;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const texto = input.value.trim();
    if (!texto) return;

    bolha(texto, 'user');
    input.value = '';
    const pensando = bolha('…', 'bot');

    try {
      const r = await fetch(BASE + 'agente/agente.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ texto, fazenda_id: fazenda })
      });
      const json = await r.json();
      pensando.textContent = json.resposta || json.erro || 'Não consegui responder.';
    } catch (err) {
      pensando.textContent = 'Erro de conexão com o agente.';
      console.error(err);
    }
  });
})();