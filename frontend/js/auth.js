// =========================================================
// auth.js — Gerenciamento de Sessão, Autenticação e UI Global
// =========================================================

// Objeto 'Auth' que centraliza a lógica de login, logout e persistência de dados do usuário
const Auth = {
  // Salva os dados do usuário autenticado no armazenamento local do navegador (localStorage)
  save(usuario) {
    localStorage.setItem('ckm_usuario', JSON.stringify(usuario));
  },

  // Recupera os dados do usuário do localStorage e converte de volta para objeto JavaScript
  get() {
    const raw = localStorage.getItem('ckm_usuario');
    // Se existir dados, retorna o objeto; caso contrário, retorna null
    return raw ? JSON.parse(raw) : null;
  },

  // Limpa os dados da sessão e redireciona o usuário para a tela de login
  logout() {
    localStorage.removeItem('ckm_usuario');
    // Força o redirecionamento para a raiz do frontend (login)
    window.location.href = '/controle-km/frontend/index.html';
  },

  // Garante que o usuário está logado e possui o perfil correto para acessar a página atual
  require(perfilEsperado = null) {
    // Busca o usuário logado
    const u = Auth.get();
    // Se não estiver logado, redireciona imediatamente para a tela de login
    if (!u) {
      window.location.href = '/controle-km/frontend/index.html';
      return null;
    }
    // Se a página exige um perfil específico (ex: 'admin') e o usuário não o possui
    if (perfilEsperado && u.perfil !== perfilEsperado) {
      // Determina a rota correta para o perfil do usuário atual
      const rota = u.perfil === 'admin'
        ? '/controle-km/frontend/admin/index.html'
        : '/controle-km/frontend/motorista/index.html';
      // Redireciona o usuário intruso para seu painel de direito
      window.location.href = rota;
      return null;
    }
    // Se tudo estiver OK, retorna os dados do usuário
    return u;
  },
};

// ── Função para exibir Notificações (Toast) na tela ──────────────────────────────
function showToast(msg, tipo = 'success') {
  // Procura pelo container de toasts na página
  let container = document.getElementById('toast-container');
  // Se o container ainda não existir, cria um novo dinamicamente e adiciona ao corpo da página
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  // Mapeia ícones baseados no tipo de mensagem
  const icons = { success: '✅', error: '❌', info: 'ℹ️' };
  // Cria o elemento da notificação (Toast)
  const toast = document.createElement('div');
  // Define a classe CSS baseada no tipo (para cores diferentes)
  toast.className = `toast ${tipo}`;
  // Preenche o conteúdo HTML com o ícone e a mensagem recebida
  toast.innerHTML = `<span>${icons[tipo]}</span><span>${msg}</span>`;
  // Adiciona o toast ao container
  container.appendChild(toast);

  // Programa a remoção automática do toast após 3.5 segundos (3500ms)
  setTimeout(() => toast.remove(), 3500);
}

// ── Preenche informações reais do usuário na barra lateral (Sidebar) ───────
function preencherSidebar(usuario) {
  // Seleciona os elementos da interface onde as informações serão exibidas
  const nomeEl  = document.getElementById('sidebar-nome');
  const roleEl  = document.getElementById('sidebar-role');
  const avatarEl = document.getElementById('sidebar-avatar');

  // Se o elemento de nome existir, define o texto como o nome do usuário
  if (nomeEl)   nomeEl.textContent  = usuario.nome;
  // Se o elemento de cargo existir, traduz o perfil técnico para um nome legível
  if (roleEl)   roleEl.textContent  = usuario.perfil === 'admin' ? 'Administrador' : 'Motorista';
  // Se o elemento de avatar existir, define o texto como a primeira letra do nome em maiúsculo
  if (avatarEl) avatarEl.textContent = usuario.nome.charAt(0).toUpperCase();

  // Configura o evento de clique no botão de sair (Logout)
  const logoutBtn = document.getElementById('btn-logout');
  if (logoutBtn) logoutBtn.addEventListener('click', Auth.logout);
}

// ── Inicializa o comportamento do Menu em telas pequenas (Mobile) ───────────────────────────────
function initMobileMenu() {
  // Busca o botão de toggle do menu mobile e a sidebar
  const btn     = document.getElementById('mobile-menu-btn');
  const sidebar = document.querySelector('.sidebar');
  // Se algum dos elementos não for encontrado, interrompe a execução
  if (!btn || !sidebar) return;

  // Alterna a classe 'open' na sidebar ao clicar no botão do menu
  btn.addEventListener('click', () => sidebar.classList.toggle('open'));
  
  // Fecha o menu automaticamente se o usuário clicar fora da sidebar
  document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !btn.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
}

