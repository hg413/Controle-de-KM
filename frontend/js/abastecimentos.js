// =========================================================
// abastecimentos.js — Lógica de Gestão de Abastecimentos
// =========================================================

// Função principal que inicializa os eventos e carrega os dados iniciais na tela
function initAbastecimentos() {
  // Chama a função para buscar e exibir os dados na tabela
  carregarTabela();
  // Busca veículos e motoristas para preencher as listas de seleção (selects) do formulário
  preencherSelects();

  // Configura o evento para abrir o modal
  document.getElementById('btn-novo-abastecimento').addEventListener('click', () => abrirModal());
  
  // Configura o evento de busca em tempo real
  const inputBusca = document.getElementById('busca-abastecimento');
  if (inputBusca) {
    inputBusca.addEventListener('input', () => carregarTabela());
  }
  // Configura os eventos para fechar o modal ao clicar no 'X' ou no botão Cancelar
  document.getElementById('modal-fechar').addEventListener('click', fecharModal);
  document.getElementById('btn-cancelar').addEventListener('click', fecharModal);

  // Intercepta o envio do formulário de cadastro de abastecimento
  document.getElementById('form-abastecimento').addEventListener('submit', async (e) => {
    // Evita o recarregamento padrão da página ao enviar o formulário
    e.preventDefault();
    // Seleciona o botão de salvar para indicar processamento
    const btn = document.getElementById('btn-salvar');
    // Adiciona um ícone de carregamento e desabilita o botão para evitar cliques duplos
    btn.innerHTML = '<span class="spinner"></span>';
    btn.disabled = true;

    // Coleta todos os valores preenchidos no formulário em um objeto de dados
    const data = {
      veiculo_id: document.getElementById('ab-veiculo').value, // ID do carro selecionado
      motorista_id: document.getElementById('ab-motorista').value, // ID do motorista selecionado
      data_abastecimento: document.getElementById('ab-data').value, // Data informada
      km_atual: document.getElementById('ab-km').value, // Quilometragem no momento
      litros: document.getElementById('ab-litros').value, // Quantidade de litros
      valor_total: document.getElementById('ab-valor').value, // Preço total pago
      posto: document.getElementById('ab-posto').value.trim() // Nome do posto (sem espaços extras)
    };

    // Envia os dados para a API e aguarda a resposta do servidor
    const res = await Api.createAbastecimento(data);

    // Se a requisição foi bem-sucedida (status 200 ou 201)
    if (res.ok) {
        // Exibe uma mensagem de sucesso (Toast)
        showToast(res.data.message || 'Abastecimento registrado!', 'success');
        // Fecha a janela modal
        fecharModal();
        // Atualiza a tabela na tela para mostrar o novo registro
        carregarTabela();
    } else {
        // Exibe mensagem de erro caso algo tenha falhado no backend
        showToast(res.data.message || 'Erro ao salvar.', 'error');
    }

    // Restaura o texto original e habilita o botão de salvar novamente
    btn.textContent = 'Salvar';
    btn.disabled = false;
  });
}

// Busca dados dinâmicos para preencher os menus suspensos (selects) do modal
async function preencherSelects() {
  // Dispara as duas buscas (veículos e usuários) simultaneamente para ganhar tempo
  const [resV, resU] = await Promise.all([Api.getVeiculos(), Api.getUsuarios()]);
  
  // Seleciona os elementos select do HTML
  const selV = document.getElementById('ab-veiculo');
  const selU = document.getElementById('ab-motorista');

  // Se a busca de veículos deu certo, preenche as opções
  if (resV.ok) {
    selV.innerHTML = '<option value="">Selecione um veículo...</option>' + 
      resV.data.map(v => `<option value="${v.id}">${v.placa}</option>`).join('');
  }

  // Se a busca de usuários deu certo, preenche as opções de motoristas
  if (resU.ok) {
    selU.innerHTML = '<option value="">Selecione o motorista...</option>' + 
      resU.data.map(u => `<option value="${u.id}">${u.nome}</option>`).join('');
  }
}

// Gera o conteúdo da tabela HTML com base nos registros do banco de dados
async function carregarTabela() {
  // Seleciona o container onde a tabela será montada
  const wrapper = document.getElementById('tabela-wrapper');
  // Exibe um estado visual de carregamento
  wrapper.innerHTML = '<div class="empty-state">⏳ Carregando...</div>';

  // Faz a chamada à API para obter todos os abastecimentos
  const { ok, data: allData } = await Api.getAbastecimentos();

  // Se a busca falhar ou não houver dados
  if (!ok || !Array.isArray(allData)) {
    wrapper.innerHTML = '<div class="empty-state">⛽ Erro ao carregar abastecimentos.</div>';
    return;
  }

  // Filtragem por busca (se houver termo digitado)
  const termo = document.getElementById('busca-abastecimento')?.value.toLowerCase().trim() || '';
  const data = allData.filter(a => {
    const placa = (a.placa || '').toLowerCase();
    const motorista = (a.motorista_nome || '').toLowerCase();
    return placa.includes(termo) || motorista.includes(termo);
  });

  // Caso não existam dados após o filtro
  if (data.length === 0) {
    wrapper.innerHTML = `<div class="empty-state">⛽ ${termo ? 'Nenhum resultado para "' + termo + '"' : 'Nenhum abastecimento registrado.'}</div>`;
    return;
  }

  // Mapeia os dados recebidos para criar as linhas da tabela (HTML strings)
  const rows = data.map(a => `
    <tr>
      <td>${new Date(a.data_abastecimento).toLocaleDateString('pt-BR')}</td> <!-- Formata data para o padrão brasileiro -->
      <td><strong>${a.placa}</strong></td> <!-- Placa do veículo em destaque -->
      <td>${a.motorista_nome}</td> <!-- Nome do motorista -->
      <td>${a.km_atual} KM</td> <!-- Quilometragem registrada -->
      <td>${a.litros} L</td> <!-- Litros abastecidos -->
      <td>R$ ${parseFloat(a.valor_total).toFixed(2)}</td> <!-- Valor formatado em moeda -->
      <td>${a.posto || '-'}</td> <!-- Nome do posto ou traço se vazio -->
      <td>
        <div style="display:flex; gap:8px;">
        <!-- Exibe botão de exclusão apenas se o usuário logado for administrador -->
        ${Auth.get().perfil === 'admin' ? `
          <button class="btn-icon btn-delete" onclick="deletarAbastecimento(${a.id})" title="Excluir">🗑️</button>
        ` : ''}
      </div>
      </td>
    </tr>
  `).join('');

  // Seta o cabeçalho e o corpo da tabela no HTML principal
  wrapper.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Data</th>
          <th>Veículo</th>
          <th>Motorista</th>
          <th>KM</th>
          <th>Litros</th>
          <th>Total</th>
          <th>Posto</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  `;

  // ── CÁLCULO DAS MÉTRICAS ──
  const agora = new Date();
  const mesAtual = agora.getMonth();
  const anoAtual = agora.getFullYear();

  // Filtra apenas os registros do mês atual
  const doMes = data.filter(a => {
    const d = new Date(a.data_abastecimento);
    return d.getMonth() === mesAtual && d.getFullYear() === anoAtual;
  });

  // Totais acumulados
  const totalLitros = data.reduce((s, a) => s + parseFloat(a.litros || 0), 0);
  const totalKM     = data.reduce((s, a) => s + parseFloat(a.km_atual || 0), 0);
  const gastoMes    = doMes.reduce((s, a) => s + parseFloat(a.valor_total || 0), 0);
  const gastoTotal  = data.reduce((s, a) => s + parseFloat(a.valor_total || 0), 0);

  // Consumo médio: km_máx - km_mín / total litros
  const kms = data.map(a => parseFloat(a.km_atual)).filter(k => k > 0).sort((a,b) => a-b);
  let consumoMedio = '--';
  if (kms.length >= 2 && totalLitros > 0) {
    const kmPercorrido = kms[kms.length - 1] - kms[0];
    consumoMedio = (kmPercorrido / totalLitros).toFixed(1);
  }

  // Custo por km
  let custoPorKm = '--';
  const kmPercTotal = kms.length >= 2 ? kms[kms.length-1] - kms[0] : 0;
  if (kmPercTotal > 0 && gastoTotal > 0) {
    custoPorKm = (gastoTotal / kmPercTotal).toFixed(2);
  }

  // Preenche os cards
  if (document.getElementById('consumo-medio')) {
    document.getElementById('consumo-medio').textContent = consumoMedio !== '--' ? `${consumoMedio} km/L` : '-- km/L';
    document.getElementById('gasto-total').textContent   = `R$ ${gastoMes.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
    document.getElementById('custo-km').textContent      = custoPorKm !== '--' ? `R$ ${custoPorKm}` : 'R$ --';
  }
}

// Torna visível a janela modal de cadastro, setando a data atual como padrão
function abrirModal() {
  document.getElementById('modal-abastecimento').classList.add('active');
  // Preenche o campo de data com o dia de hoje no formato YYYY-MM-DD
  document.getElementById('ab-data').value = new Date().toISOString().split('T')[0];
}

// Oculta a janela modal e limpa todos os campos preenchidos anteriormente
function fecharModal() {
  document.getElementById('modal-abastecimento').classList.remove('active');
  document.getElementById('form-abastecimento').reset();
}

// Função invocada ao clicar no botão de lixeira para remover um registro
async function deletarAbastecimento(id) {
  // Solicita confirmação do usuário antes de proceder com a deleção
  if (!confirm('Deseja excluir este registro de abastecimento?')) return;
  // Faz a chamada de exclusão para o servidor via API
  const { ok } = await Api.deleteAbastecimento(id);
  // Se excluído com sucesso
  if (ok) {
    // Informa ao usuário via Toast e recarrega a tabela para refletir a mudança
    showToast('Registro excluído.', 'success');
    carregarTabela();
  }
}

