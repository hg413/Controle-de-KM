// =============================================
// manutencoes.js — Gestão de Manutenções
// =============================================

function initManutencoes() {
  carregarTabela();
  preencherVeiculos();

  document.getElementById('btn-nova-manutencao').addEventListener('click', () => abrirModal());
  document.getElementById('modal-fechar').addEventListener('click', fecharModal);
  document.getElementById('btn-cancelar').addEventListener('click', fecharModal);

  document.getElementById('form-manutencao').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-salvar');
    btn.innerHTML = '<span class="spinner"></span>';
    btn.disabled = true;

    const data = {
      veiculo_id: document.getElementById('ma-veiculo').value,
      data_manutencao: document.getElementById('ma-data').value,
      descricao: document.getElementById('ma-desc').value.trim(),
      valor_total: document.getElementById('ma-valor').value,
      km_veiculo: document.getElementById('ma-km').value,
      tipo: document.getElementById('ma-tipo').value,
      realizada_por: document.getElementById('ma-oficina').value.trim()
    };

    const res = await Api.createManutencao(data);

    if (res.ok) {
      showToast(res.data.message || 'Manutenção registrada!', 'success');
      fecharModal();
      carregarTabela();
    } else {
      showToast(res.data.message || 'Erro ao salvar.', 'error');
    }

    btn.textContent = 'Salvar';
    btn.disabled = false;
  });
}

async function preencherVeiculos() {
  const { ok, data } = await Api.getVeiculos();
  const selV = document.getElementById('ma-veiculo');

  if (ok) {
    selV.innerHTML = '<option value="">Selecione um veículo...</option>' + 
      data.map(v => `<option value="${v.id}">${v.placa}</option>`).join('');
  }
}

async function carregarTabela() {
  const wrapper = document.getElementById('tabela-wrapper');
  wrapper.innerHTML = '<div class="empty-state">⏳ Carregando...</div>';

  const { ok, data } = await Api.getManutencoes();

  if (!ok || !Array.isArray(data) || data.length === 0) {
    wrapper.innerHTML = '<div class="empty-state">🔧 Nenhuma manutenção registrada.</div>';
    return;
  }

  const rows = data.map(m => `
    <tr>
      <td>${new Date(m.data_manutencao).toLocaleDateString('pt-BR')}</td>
      <td><strong>${m.placa}</strong></td>
      <td><span class="badge badge-${m.tipo}">${m.tipo.toUpperCase()}</span></td>
      <td>${m.descricao.substring(0, 30)}${m.descricao.length > 30 ? '...' : ''}</td>
      <td>R$ ${parseFloat(m.valor_total).toFixed(2)}</td>
      <td>${m.realizada_por || '-'}</td>
      <td>
        ${Auth.get().perfil === 'admin' ? `
          <button class="btn btn-danger btn-sm" onclick="excluir(${m.id})" title="Excluir">🗑️</button>
        ` : '-'}
      </td>
    </tr>
  `).join('');

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

function abrirModal() {
  document.getElementById('modal-manutencao').classList.add('active');
  document.getElementById('ma-data').value = new Date().toISOString().split('T')[0];
}

function fecharModal() {
  document.getElementById('modal-manutencao').classList.remove('active');
  document.getElementById('form-manutencao').reset();
}

async function excluir(id) {
  if (!confirm('Deseja excluir este registro de manutenção?')) return;
  const { ok } = await Api.deleteManutencao(id);
  if (ok) {
    showToast('Registro excluído.', 'success');
    carregarTabela();
  }
}
