// =========================================================
// veiculos.js — Gestão de Cadastro de Veículos (CRUD)
// =========================================================

// Função que inicializa a página de veículos, registrando eventos e carregando a frota
function initVeiculos() {
  // Faz a primeira carga de veículos na tabela
  carregarVeiculos();

  // Configura o botão para mostrar o modal de cadastro de novo veículo
  document.getElementById('btn-novo-veiculo').addEventListener('click', () => {
    document.getElementById('modal-veiculo').classList.add('active');
  });

  // Configura os eventos de fechamento do modal (clique no 'X', no cancelar ou no fundo)
  document.getElementById('modal-fechar').addEventListener('click', fecharModalVeiculo);
  document.getElementById('btn-cancelar').addEventListener('click', fecharModalVeiculo);
  document.getElementById('modal-veiculo').addEventListener('click', (e) => {
    // Verifica se o clique foi fora do formulário para fechar
    if (e.target === e.currentTarget) fecharModalVeiculo();
  });

  // Gerencia o envio dos dados do novo veículo para o backend
  document.getElementById('form-veiculo').addEventListener('submit', async (e) => {
    // Impede o recarregamento automático da página
    e.preventDefault();
    // Coleta a placa e o ID do motorista, limpando espaços e padronizando para maiúsculas
    const placa      = document.getElementById('veiculo-placa').value.trim().toUpperCase();
    const modelo     = document.getElementById('veiculo-modelo').value;
    const motoristaId = document.getElementById('veiculo-motorista').value || null;

    // Sinaliza visualmente que o cadastro está processando
    const btn = document.getElementById('btn-salvar');
    btn.innerHTML = '<span class="spinner"></span>';
    btn.disabled = true;

    // Realiza a chamada POST para criar o veículo via API
    const { ok, data } = await Api.createVeiculo(placa, motoristaId, modelo);

    // Se a criação foi bem-sucedida
    if (ok) {
        // Exibe feedback de sucesso e fecha o modal
        showToast(data.message || 'Veículo cadastrado!', 'success');
        fecharModalVeiculo();
        // Atualiza a tabela para exibir o novo veículo
        carregarVeiculos();
    } else {
        // Exibe mensagem de erro caso o cadastro falhe no servidor
        showToast(data.message || 'Erro ao cadastrar.', 'error');
    }

    // Retorna o botão para o estado original
    btn.textContent = 'Cadastrar';
    btn.disabled = false;
  });
}

// Busca a lista de veículos e motoristas para montar a interface
async function carregarVeiculos() {
  // Seleciona o container da tabela e exibe estado de carregamento
  const wrapper = document.getElementById('tabela-wrapper');
  wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">⏳</div><p>Carregando...</p></div>';

  // Executa as buscas de veículos e usuários em paralelo para otimizar tempo
  const [veiculosRes, usuariosRes] = await Promise.all([Api.getVeiculos(), Api.getUsuarios()]);

  // Filtra os usuários para mostrar tanto motoristas quanto administradores, caso queiram assumir carro
  const motoristas = Array.isArray(usuariosRes.data)
    ? usuariosRes.data.filter(u => u.perfil === 'motorista' || u.perfil === 'admin')
    : [];

  // Preenche dinamicamente o select do formulário com os motoristas disponíveis
  const select = document.getElementById('veiculo-motorista');
  select.innerHTML = '<option value="">Nenhum</option>';
  motoristas.forEach(m => {
    const opt = document.createElement('option');
    opt.value = m.id;
    opt.textContent = m.nome;
    select.appendChild(opt);
  });

  const data = veiculosRes.data;
  // Caso a API retorne vazia ou com erro, exibe mensagem de lista vazia
  if (!veiculosRes.ok || !Array.isArray(data) || data.length === 0) {
    wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">🚙</div><p>Nenhum veículo cadastrado.</p></div>';
    return;
  }

  // Mapeia o array de veículos para gerar as linhas (tr) da tabela HTML
  const rows = data.map(v => `
    <tr>
      <td><strong>${v.placa}</strong></td> <!-- Exibe a placa em negrito -->
      <td>${v.modelo || '<span style="color:var(--text-secondary)">—</span>'}</td> <!-- Exibe o modelo -->
      <td>${v.motorista_responsavel || '<span style="color:var(--text-secondary)">—</span>'}</td> <!-- Nome do motorista ou traço -->
      <td>
        <!-- Botão para remover o veículo do sistema -->
        <button class="btn-icon btn-delete" onclick="excluirVeiculo(${v.id}, '${v.placa}')" title="Excluir">🗑️</button>
      </td>
    </tr>
  `).join('');

  // Seta a estrutura da tabela no wrapper principal
  wrapper.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Placa</th>
          <th>Modelo</th>
          <th>Motorista Responsável</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  `;
}

// Limpa os campos do formulário e esconde o modal de veículos
function fecharModalVeiculo() {
  document.getElementById('modal-veiculo').classList.remove('active');
  document.getElementById('form-veiculo').reset();
}

// Inicia a deleção de um veículo após confirmação do usuário administrador
async function excluirVeiculo(id, placa) {
  // Validação de segurança para evitar exclusão acidental
  if (!confirm(`Deseja excluir o veículo de placa "${placa}"?`)) return;

  // Realiza o comando DELETE na API
  const { ok, data } = await Api.deleteVeiculo(id);
  // No sucesso, exibe mensagem e recarrega a grid
  if (ok) {
    showToast(data.message || 'Veículo excluído.', 'success');
    carregarVeiculos();
  } else {
    // Caso contrário, mostra o erro retornado
    showToast(data.message || 'Erro ao excluir.', 'error');
  }
}

