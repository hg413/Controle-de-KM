// =============================================
// usuarios.js — CRUD de Usuários
// =============================================

function initUsuarios() {
  carregarUsuarios();

  // Abre modal para novo usuário
  document.getElementById('btn-novo-usuario').addEventListener('click', () => {
    abrirModal(null);
  });

  // Fecha modal
  document.getElementById('modal-fechar').addEventListener('click', fecharModal);
  document.getElementById('btn-cancelar').addEventListener('click', fecharModal);
  document.getElementById('modal-usuario').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) fecharModal();
  });

  // Submete formulário
  document.getElementById('form-usuario').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id     = document.getElementById('usuario-id').value;
    const nome   = document.getElementById('usuario-nome').value.trim();
    const email  = document.getElementById('usuario-email').value.trim();
    const senha  = document.getElementById('usuario-senha').value;
    const perfil = document.getElementById('usuario-perfil').value;

    const btn = document.getElementById('btn-salvar');
    btn.innerHTML = '<span class="spinner"></span>';
    btn.disabled = true;

    let res;
    if (id) {
      // Editar
      res = await Api.updateUsuario(id, nome, email, perfil, senha);
    } else {
      // Criar
      if (!senha) { showToast('A senha é obrigatória.', 'error'); btn.textContent = 'Salvar'; btn.disabled = false; return; }
      res = await Api.createUsuario(nome, email, senha, perfil);
    }

    if (res.ok) {
      showToast(res.data.message || 'Operação realizada!', 'success');
      fecharModal();
      carregarUsuarios();
    } else {
      showToast(res.data.message || 'Erro ao salvar.', 'error');
    }

    btn.textContent = 'Salvar';
    btn.disabled = false;
  });
}

async function carregarUsuarios() {
  const wrapper = document.getElementById('tabela-wrapper');
  wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">⏳</div><p>Carregando...</p></div>';

  const { ok, data } = await Api.getUsuarios();

  if (!ok || !Array.isArray(data) || data.length === 0) {
    wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">👥</div><p>Nenhum usuário cadastrado.</p></div>';
    return;
  }

  const rows = data.map(u => `
    <tr>
      <td><strong>${u.nome}</strong></td>
      <td>${u.email}</td>
      <td><span class="badge badge-${u.perfil}">${u.perfil === 'admin' ? 'Administrador' : 'Motorista'}</span></td>
      <td>
        <div style="display:flex;gap:6px;">
          <button class="btn btn-ghost btn-sm" onclick="abrirModal(${JSON.stringify(u).replace(/"/g, '&quot;')})">✏️ Editar</button>
          <button class="btn btn-danger btn-sm" onclick="excluirUsuario(${u.id}, '${u.nome}')">🗑️ Excluir</button>
        </div>
      </td>
    </tr>
  `).join('');

  wrapper.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Perfil</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  `;
}

function abrirModal(usuario) {
  const modal = document.getElementById('modal-usuario');
  document.getElementById('modal-titulo').textContent = usuario ? 'Editar Usuário' : 'Novo Usuário';
  document.getElementById('usuario-id').value    = usuario?.id    ?? '';
  document.getElementById('usuario-nome').value  = usuario?.nome  ?? '';
  document.getElementById('usuario-email').value = usuario?.email ?? '';
  document.getElementById('usuario-senha').value = usuario?.senha ?? '';
  document.getElementById('usuario-perfil').value = usuario?.perfil ?? '';

  // Senha obrigatória só no cadastro
  const senhaInput = document.getElementById('usuario-senha');
  senhaInput.required = !usuario;
  senhaInput.placeholder = usuario ? 'Deixe em branco para não alterar (não implementado)' : 'Mínimo 6 caracteres';

  modal.classList.add('active');
}

function fecharModal() {
  document.getElementById('modal-usuario').classList.remove('active');
  document.getElementById('form-usuario').reset();
  document.getElementById('usuario-id').value = '';
}

async function excluirUsuario(id, nome) {
  if (!confirm(`Tem certeza que deseja excluir o usuário "${nome}"?`)) return;

  const { ok, data } = await Api.deleteUsuario(id);
  if (ok) {
    showToast(data.message || 'Usuário excluído.', 'success');
    carregarUsuarios();
  } else {
    showToast(data.message || 'Erro ao excluir.', 'error');
  }
}
