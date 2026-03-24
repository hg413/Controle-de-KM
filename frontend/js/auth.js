// =============================================
// auth.js — Sessão e autenticação
// =============================================

const Auth = {
  save(usuario) {
    localStorage.setItem('ckm_usuario', JSON.stringify(usuario));
  },

  get() {
    const raw = localStorage.getItem('ckm_usuario');
    return raw ? JSON.parse(raw) : null;
  },

  logout() {
    localStorage.removeItem('ckm_usuario');
    window.location.href = '/controle-km/frontend/index.html';
  },

  // Garante que o usuário está logado; redireciona se não estiver
  require(perfilEsperado = null) {
    const u = Auth.get();
    if (!u) {
      window.location.href = '/controle-km/frontend/index.html';
      return null;
    }
    if (perfilEsperado && u.perfil !== perfilEsperado) {
      // Perfil errado: manda para o painel correto
      const rota = u.perfil === 'admin'
        ? '/controle-km/frontend/admin/index.html'
        : '/controle-km/frontend/motorista/index.html';
      window.location.href = rota;
      return null;
    }
    return u;
  },
};

// ── Toast Global ──────────────────────────────
function showToast(msg, tipo = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const icons = { success: '✅', error: '❌', info: 'ℹ️' };
  const toast = document.createElement('div');
  toast.className = `toast ${tipo}`;
  toast.innerHTML = `<span>${icons[tipo]}</span><span>${msg}</span>`;
  container.appendChild(toast);

  setTimeout(() => toast.remove(), 3500);
}

// ── Preenche info do usuário na sidebar ───────
function preencherSidebar(usuario) {
  const nomeEl  = document.getElementById('sidebar-nome');
  const roleEl  = document.getElementById('sidebar-role');
  const avatarEl = document.getElementById('sidebar-avatar');

  if (nomeEl)   nomeEl.textContent  = usuario.nome;
  if (roleEl)   roleEl.textContent  = usuario.perfil === 'admin' ? 'Administrador' : 'Motorista';
  if (avatarEl) avatarEl.textContent = usuario.nome.charAt(0).toUpperCase();

  const logoutBtn = document.getElementById('btn-logout');
  if (logoutBtn) logoutBtn.addEventListener('click', Auth.logout);
}

// ── Menu mobile ───────────────────────────────
function initMobileMenu() {
  const btn     = document.getElementById('mobile-menu-btn');
  const sidebar = document.querySelector('.sidebar');
  if (!btn || !sidebar) return;

  btn.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !btn.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
}
