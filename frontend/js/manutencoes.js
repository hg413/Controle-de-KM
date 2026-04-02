// =========================================================
// manutencoes.js — Lógica de Gestão de Manutenções
// =========================================================

// Inicializa a tela de manutenções, configurando eventos e carregando dados
function initManutencoes() {
  // Carrega a lista de manutenções na tabela principal
  carregarTabela();
  // Busca a lista de veículos para preencher o campo de seleção no modal
  preencherVeiculos();

  // Define o evento para abrir o modal de cadastro ao clicar no botão "Nova Manutenção"
  document.getElementById('btn-nova-manutencao').addEventListener('click', () => abrirModal());
  // Define os eventos para fechar o modal (botão fechar 'X' e botão cancelar)
  document.getElementById('modal-fechar').addEventListener('click', fecharModal);
  document.getElementById('btn-cancelar').addEventListener('click', fecharModal);

  // Manipula a submissão do formulário de registro de manutenção
  document.getElementById('form-manutencao').addEventListener('submit', async (e) => {
    // Impede o comportamento padrão de atualizar a página ao enviar o form
    e.preventDefault();
    // Prepara o botão de salvar com um visual de carregamento
    const btn = document.getElementById('btn-salvar');
    btn.innerHTML = '<span class="spinner"></span>';
    btn.disabled = true;

    // Coleta as informações digitadas pelo usuário no formulário
    const data = {
      veiculo_id: document.getElementById('ma-veiculo').value, // ID do veículo
      data_manutencao: document.getElementById('ma-data').value, // Data do serviço
      descricao: document.getElementById('ma-desc').value.trim(), // Relato do que foi consertado
      valor_total: document.getElementById('ma-valor').value, // Preço pago
      km_veiculo: document.getElementById('ma-km').value, // Quilometragem do veículo
      tipo: document.getElementById('ma-tipo').value, // Preventiva ou Corretiva
      realizada_por: document.getElementById('ma-oficina').value.trim() // Nome da oficina ou mecânico
    };

    // Envia os dados para salvar no banco via API
    const res = await Api.createManutencao(data);

    // Se o salvamento foi um sucesso
    if (res.ok) {
        // Exibe notificação de sucesso
        showToast(res.data.message || 'Manutenção registrada!', 'success');
        // Fecha o formulário
        fecharModal();
        // Recarrega a listagem para mostrar o novo item
        carregarTabela();
    } else {
        // Exibe erro caso o servidor retorne falha
        showToast(res.data.message || 'Erro ao salvar.', 'error');
    }

    // Retorna o botão para o estado normal (estático e habilitado)
    btn.textContent = 'Salvar';
    btn.disabled = false;
  });
}

// Busca a frota de veículos para permitir a seleção no formulário de cadastro
async function preencherVeiculos() {
  // Chama a API de consulta de veículos
  const { ok, data } = await Api.getVeiculos();
  // Referência ao campo de seleção (select)
  const selV = document.getElementById('ma-veiculo');

  // Se houver veículos cadastrados, gera as opções HTML dinamicamente
  if (ok) {
    selV.innerHTML = '<option value="">Selecione um veículo...</option>' + 
      data.map(v => `<option value="${v.id}">${v.placa}</option>`).join('');
  }
}

// Constrói e exibe a tabela de histórico de manutenções
async function carregarTabela() {
  // Seleciona o local onde a tabela será renderizada
  const wrapper = document.getElementById('tabela-wrapper');
  // Indica ao usuário que os dados estão sendo buscados
  wrapper.innerHTML = '<div class="empty-state">⏳ Carregando...</div>';

  // Busca o histórico de manutenções via API
  const { ok, data } = await Api.getManutencoes();

  // Verifica se há registros para exibir
  if (!ok || !Array.isArray(data) || data.length === 0) {
    wrapper.innerHTML = '<div class="empty-state">🔧 Nenhuma manutenção registrada.</div>';
    return;
  }

  // Mapeia os registros para gerar as linhas (tr) da tabela
  const rows = data.map(m => `
    <tr>
      <td>${new Date(m.data_manutencao).toLocaleDateString('pt-BR')}</td> <!-- Formata data para o BR -->
      <td><strong>${m.placa}</strong></td> <!-- Placa em negrito -->
      <td><span class="badge badge-${m.tipo}">${m.tipo.toUpperCase()}</span></td> <!-- Badge visual para o tipo -->
      <td>${m.descricao.substring(0, 30)}${m.descricao.length > 30 ? '...' : ''}</td> <!-- Resumo da descrição -->
      <td>R$ ${parseFloat(m.valor_total).toFixed(2)}</td> <!-- Custo formatado como moeda -->
      <td>${m.realizada_por || '-'}</td> <!-- Oficina executor -->
      <td>
        <!-- Botão de exclusão visível apenas para administradores -->
        ${Auth.get().perfil === 'admin' ? `
          <button class="btn-icon btn-delete" onclick="excluir(${m.id})" title="Excluir">🗑️</button>
        ` : '-'}
      </td>
    </tr>
  `).join('');

  // Seta a tabela final montada no wrapper
  wrapper.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Data</th>
          <th>Veículo</th>
          <th>Tipo</th>
          <th>Descrição</th>
          <th>Valor</th>
          <th>Oficina/Local</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  `;
}

// Exibe o modal de cadastro e preenche a data de hoje como sugestão
function abrirModal() {
  document.getElementById('modal-manutencao').classList.add('active');
  document.getElementById('ma-data').value = new Date().toISOString().split('T')[0];
}

// Esconde o modal e limpa o formulário de preenchimento
function fecharModal() {
  document.getElementById('modal-manutencao').classList.remove('active');
  document.getElementById('form-manutencao').reset();
}

// Envia comando para apagar uma manutenção após confirmação
async function excluir(id) {
  // Pergunta ao usuário se ele tem certeza da ação
  if (!confirm('Deseja excluir este registro de manutenção?')) return;
  // Chama a API de deleção
  const { ok } = await Api.deleteManutencao(id);
  // Em caso de sucesso
  if (ok) {
    // Notifica e atualiza a visualização
    showToast('Registro excluído.', 'success');
    carregarTabela();
  }
}

