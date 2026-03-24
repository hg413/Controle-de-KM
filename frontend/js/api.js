// =============================================
// api.js — Chamadas HTTP centralizadas
// =============================================

// Calcula o caminho absoluto até a pasta backend/api
const getBaseUrl = () => {
    const root = window.location.pathname.split('/frontend')[0];
    return root + '/backend/api';
};

const BASE_URL = getBaseUrl();

async function apiFetch(endpoint, method = 'GET', body = null) {
  const options = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  if (body) options.body = JSON.stringify(body);

  const res = await fetch(`${BASE_URL}/${endpoint}`, options);
  const data = await res.json().catch(() => ({}));
  return { ok: res.ok, status: res.status, data };
}

const Api = {
  // Auth
  login: (email, senha) => apiFetch('login.php', 'POST', { email, senha }),

  // Usuários
  getUsuarios: ()                        => apiFetch('usuarios.php'),
  createUsuario: (nome, email, senha, perfil) => apiFetch('usuarios.php', 'POST', { nome, email, senha, perfil }),
  updateUsuario: (id, nome, email, perfil, senha)    => apiFetch('usuarios.php', 'PUT',  { id, nome, email, perfil, senha }),
  deleteUsuario: (id)                         => apiFetch('usuarios.php', 'DELETE', { id }),

  // Veículos
  getVeiculos: ()                          => apiFetch('veiculos.php'),
  createVeiculo: (placa, motorista_id)     => apiFetch('veiculos.php', 'POST', { placa, motorista_responsavel_id: motorista_id || null }),
  deleteVeiculo: (id)                      => apiFetch('veiculos.php', 'DELETE', { id }),

  // Abastecimentos
  getAbastecimentos: () => apiFetch('abastecimentos.php'),
  createAbastecimento: (data) => apiFetch('abastecimentos.php', 'POST', data),
  deleteAbastecimento: (id) => apiFetch('abastecimentos.php', 'DELETE', { id }),

  // Manutenções
  getManutencoes: () => apiFetch('manutencoes.php'),
  createManutencao: (data) => apiFetch('manutencoes.php', 'POST', data),
  deleteManutencao: (id) => apiFetch('manutencoes.php', 'DELETE', { id }),
};
