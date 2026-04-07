// =========================================================
// api.js — Centralização de todas as chamadas HTTP (Fetch)
// =========================================================

// Função para calcular dinamicamente o caminho do backend com base na localização atual no navegador
const getBaseUrl = () => {
    // Pega o caminho da URL atual, divide pelo marcador '/frontend' e pega a primeira parte (raiz do projeto)
    const root = window.location.pathname.split('/frontend')[0];
    // Retorna o caminho concatenado com a pasta onde as APIs em PHP residem
    return root + '/backend/api';
};

// Armazena a URL base em uma constante para ser usada em todas as requisições
const BASE_URL = getBaseUrl();

// Função genérica assíncrona para realizar requisições usando a API Fetch do navegador
async function apiFetch(endpoint, method = 'GET', body = null) {
  // Define o método HTTP (GET, POST, PUT, DELETE) e o cabeçalho padrão como JSON
  const options = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  // Se houver um corpo (body) na requisição, converte o objeto JavaScript para uma string JSON
  if (body) options.body = JSON.stringify(body);

  // Faz a chamada real para o servidor e aguarda a resposta
  const res = await fetch(`${BASE_URL}/${endpoint}`, options).catch(err => {
    // Caso ocorra um erro de rede ou servidor offline, imprime no console e retorna um objeto de erro
    console.error("Erro na API:", err);
    return { ok: false, status: 500 };
  });

  // Tenta converter a resposta do servidor para um objeto JSON; se falhar, retorna um objeto vazio
  const data = await res.json().catch(() => ({}));
  
  // Retorna um objeto consolidado com o status de sucesso, o código HTTP e os dados retornados
  return { ok: res.ok, status: res.status, data };
}

// Objeto 'Api' que expõe todos os métodos de integração com o backend de forma organizada
const Api = {
  // Realiza o login enviando email e senha para o backend validar
  login: (email, senha) => apiFetch('login.php', 'POST', { email, senha }),

  // Seção de Gerenciamento de Usuários
  getUsuarios: ()                        => apiFetch('usuarios.php'), // Busca lista de todos os usuários
  createUsuario: (nome, email, senha, perfil) => apiFetch('usuarios.php', 'POST', { nome, email, senha, perfil }), // Cria novo usuário
  updateUsuario: (id, nome, email, perfil, senha)    => apiFetch('usuarios.php', 'PUT',  { id, nome, email, perfil, senha }), // Atualiza dados
  deleteUsuario: (id)                         => apiFetch('usuarios.php', 'DELETE', { id }), // Remove um usuário pelo ID

  // Seção de Gerenciamento de Veículos
  getVeiculos: ()                          => apiFetch('veiculos.php'), // Busca frota cadastrada
  createVeiculo: (placa, motorista_id, modelo)     => apiFetch('veiculos.php', 'POST', { placa, motorista_responsavel_id: motorista_id || null, modelo }), // Cadastra carro
  deleteVeiculo: (id)                      => apiFetch('veiculos.php', 'DELETE', { id }), // Apaga registro de veículo

  // Seção de Registros de Abastecimentos
  getAbastecimentos: () => apiFetch('abastecimentos.php'), // Histórico de abastecimentos
  createAbastecimento: (data) => apiFetch('abastecimentos.php', 'POST', data), // Novo abastecimento
  deleteAbastecimento: (id) => apiFetch('abastecimentos.php', 'DELETE', { id }), // Remove registro de abastecimento

  // Seção de Registros de Manutenções
  getManutencoes: () => apiFetch('manutencoes.php'), // Histórico de consertos e revisões
  createManutencao: (data) => apiFetch('manutencoes.php', 'POST', data), // Nova manutenção
  deleteManutencao: (id) => apiFetch('manutencoes.php', 'DELETE', { id }), // Remove registro de manutenção

  // Seção de Registros Diários (Diário de Bordo)
  getRegistrosDiarios: () => apiFetch('registros_diarios.php'),
  createRegistroDiario: (data) => apiFetch('registros_diarios.php', 'POST', data),
  deleteRegistroDiario: (id) => apiFetch('registros_diarios.php', 'DELETE', { id }),

  // Seção de Contratos
  getContratos: () => apiFetch('contratos.php'),
  createContrato: (nome, cliente, descricao) => apiFetch('contratos.php', 'POST', { nome, cliente, descricao }),
  deleteContrato: (id) => apiFetch('contratos.php', 'DELETE', { id }),

  // Seção de Ocorrências
  getOcorrencias: () => apiFetch('ocorrencias.php'),
  createOcorrencia: (data) => apiFetch('ocorrencias.php', 'POST', data),
  updateOcorrenciaStatus: (id, status) => apiFetch('ocorrencias.php', 'PUT', { id, status }),
  deleteOcorrencia: (id) => apiFetch('ocorrencias.php', 'DELETE', { id }),
};

