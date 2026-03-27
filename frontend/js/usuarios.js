// =========================================================
// usuarios.js — Gestão de Cadastro de Usuários (CRUD)
// =========================================================

// Função principal de inicialização da página de usuários
function initUsuarios() {
  // Carrega imediatamente a lista de usuários existentes na tabela
  carregarUsuarios();

  // Configura o evento para abrir o modal de criação de um novo usuário (parâmetro null indica novo)
  document.getElementById('btn-novo-usuario').addEventListener('click', () => {
    abrirModal(null);
  });

  // Configura os diversos eventos para fechar a janela modal (botão fechar, cancelar ou clique fora da caixa)
  document.getElementById('modal-fechar').addEventListener('click', fecharModal);
  document.getElementById('btn-cancelar').addEventListener('click', fecharModal);
  document.getElementById('modal-usuario').addEventListener('click', (e) => {
    // Se o clique foi no fundo escuro (sem ser nos campos), fecha o modal
    if (e.target === e.currentTarget) fecharModal();
  });

  // Intercepta e processa o envio do formulário de usuário
  document.getElementById('form-usuario').addEventListener('submit', async (e) => {
    // Evita que a página seja recarregada ao enviar os dados
    e.preventDefault();
    // Coleta as informações de cada campo do formulário
    const id     = document.getElementById('usuario-id').value; // ID (presente apenas se for edição)
    const nome   = document.getElementById('usuario-nome').value.trim(); // Nome do usuário
    const email  = document.getElementById('usuario-email').value.trim(); // E-mail institucional
    const senha  = document.getElementById('usuario-senha').value; // Senha (pode ser vazia na edição)
    const perfil = document.getElementById('usuario-perfil').value; // Perfil (Admin ou Motorista)

    // Altera visualmente o botão de salvar para indicar que a operação está em curso
    const btn = document.getElementById('btn-salvar');
    btn.innerHTML = '<span class="spinner"></span>';
    btn.disabled = true;

    let res;
    // Verifica se estamos editando um usuário existente ou criando um novo
    if (id) {
      // Caso exista ID, realiza uma chamada de atualização (PUT) via API
      res = await Api.updateUsuario(id, nome, email, perfil, senha);
    } else {
      // Caso não exista ID, realiza uma chamada de criação (POST)
      // Valida se a senha foi preenchida (obrigatória para novos cadastros)
      if (!senha) { 
        showToast('A senha é obrigatória.', 'error'); 
        btn.textContent = 'Salvar'; 
        btn.disabled = false; 
        return; 
      }
      res = await Api.createUsuario(nome, email, senha, perfil);
    }

    // Processa a resposta do servidor após a tentativa de salvamento
    if (res.ok) {
        // Exibe mensagem de sucesso flutuante (Toast)
        showToast(res.data.message || 'Operação realizada!', 'success');
        // Esconde o formulário
        fecharModal();
        // Atualiza a tabela para refletir as alterações
        carregarUsuarios();
    } else {
        // Exibe mensagem de erro detalhada vinda do backend
        showToast(res.data.message || 'Erro ao salvar.', 'error');
    }

    // Restaura o botão de salvar para o estado inicial
    btn.textContent = 'Salvar';
    btn.disabled = false;
  });
}

// Busca e renderiza a lista de todos os usuários cadastrados
async function carregarUsuarios() {
  // Local onde a tabela ou mensagem de vazio será inserida
  const wrapper = document.getElementById('tabela-wrapper');
  // Exibe um feedback visual de carregamento
  wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">⏳</div><p>Carregando...</p></div>';

  // Faz a chamada assíncrona para buscar os usuários
  const { ok, data } = await Api.getUsuarios();

  // Caso ocorra erro ou a lista venha vazia
  if (!ok || !Array.isArray(data) || data.length === 0) {
    wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">👥</div><p>Nenhum usuário cadastrado.</p></div>';
    return;
  }

  // Mapeia o array de usuários para construir as linhas (strings HTML) da tabela
  const rows = data.map(u => `
    <tr>
      <td><strong>${u.nome}</strong></td> <!-- Nome do usuário em destaque -->
      <td>${u.email}</td> <!-- E-mail para contato -->
      <td><span class="badge badge-${u.perfil}">${u.perfil === 'admin' ? 'Administrador' : 'Motorista'}</span></td> <!-- Tag visual do cargo -->
      <td>
        <div style="display:flex;gap:6px;">
          <!-- Botão para carregar os dados deste usuário no modal de edição -->
          <button class="btn btn-ghost btn-sm" onclick="abrirModal(${JSON.stringify(u).replace(/"/g, '&quot;')})">✏️ Editar</button>
          <!-- Botão para remover o usuário do sistema -->
          <button class="btn btn-danger btn-sm" onclick="excluirUsuario(${u.id}, '${u.nome}')">🗑️ Excluir</button>
        </div>
      </td>
    </tr>
  `).join('');

  // Seta a estrutura da tabela finalizada no DOM
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

// Prepara e exibe o modal, podendo carregar dados para edição ou limpar para novo cadastro
function abrirModal(usuario) {
  const modal = document.getElementById('modal-usuario');
  // Define o título do modal baseado na ação (editar ou criar)
  document.getElementById('modal-titulo').textContent = usuario ? 'Editar Usuário' : 'Novo Usuário';
  // Preenche os campos com os dados do usuário (se fornecidos) ou limpa-os (se nulo)
  document.getElementById('usuario-id').value    = usuario?.id    ?? '';
  document.getElementById('usuario-nome').value  = usuario?.nome  ?? '';
  document.getElementById('usuario-email').value = usuario?.email ?? '';
  document.getElementById('usuario-senha').value = ''; // Senha começa limpa por segurança
  document.getElementById('usuario-perfil').value = usuario?.perfil ?? '';

  // Configura a obrigatoriedade da senha: apenas para novos usuários
  const senhaInput = document.getElementById('usuario-senha');
  senhaInput.required = !usuario;
  // Muda o texto de dica do campo de senha
  senhaInput.placeholder = usuario ? 'Deixe em branco para não alterar' : 'Mínimo 6 caracteres';

  // Adiciona a classe que torna o modal visível na tela
  modal.classList.add('active');
}

// Esconde o modal e limpa o formulário de usuários
function fecharModal() {
  document.getElementById('modal-usuario').classList.remove('active');
  document.getElementById('form-usuario').reset();
  // Garante que o ID oculto seja resetado para não confundir o próximo cadastro
  document.getElementById('usuario-id').value = '';
}

// Inicia o processo de remoção de um usuário do sistema
async function excluirUsuario(id, nome) {
  // Tela de confirmação crítica para evitar exclusões acidentais
  if (!confirm(`Tem certeza que deseja excluir o usuário "${nome}"?`)) return;

  // Chama a API de exclusão
  const { ok, data } = await Api.deleteUsuario(id);
  // Se deletado com sucesso
  if (ok) {
    // Notifica o usuário e recarrega a grid
    showToast(data.message || 'Usuário excluído.', 'success');
    carregarUsuarios();
  } else {
    // Exibe o motivo da falha caso o backend recuse o delete
    showToast(data.message || 'Erro ao excluir.', 'error');
  }
}

