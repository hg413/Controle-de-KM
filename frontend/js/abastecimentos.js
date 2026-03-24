// =============================================
// abastecimentos.js — Gestão de Abastecimentos
// =============================================

function initAbastecimentos() {
  carregarTabela();
  preencherSelects();

  document.getElementById('btn-novo-abastecimento').addEventListener('click', () => abrirModal());
  document.getElementById('modal-fechar').addEventListener('click', fecharModal);
  document.getElementById('btn-cancelar').addEventListener('click', fecharModal);

  document.getElementById('form-abastecimento').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-salvar');
    btn.innerHTML = '<span class="spinner"></span>';
    btn.disabled = true;

    const data = {
      veiculo_id: document.getElementById('ab-veiculo').value,
      motorista_id: document.getElementById('ab-motorista').value,
      data_abastecimento: document.getElementById('ab-data').value,
      km_atual: document.getElementById('ab-km').value,
      litros: document.getElementById('ab-litros').value,
      valor_total: document.getElementById('ab-valor').value,
      posto: document.getElementById('ab-posto').value.trim()
    };

    const res = await Api.createAbastecimento(data);

    if (res.ok) {
      showToast(res.data.message || 'Abastecimento registrado!', 'success');
      fecharModal();
      carregarTabela();
    } else {
      showToast(res.data.message || 'Erro ao salvar.', 'error');
    }

    btn.textContent = 'Salvar';
    btn.disabled = false;
  });
}

async function preencherSelects() {
  const [resV, resU] = await Promise.all([Api.getVeiculos(), Api.getUsuarios()]);
  
  const selV = document.getElementById('ab-veiculo');
  const selU = document.getElementById('ab-motorista');

  if (resV.ok) {
    selV.innerHTML = '<option value="">Selecione um veículo...</option>' + 
      resV.data.map(v => `<option value="${v.id}">${v.placa}</option>`).join('');
  }

  if (resU.ok) {
    selU.innerHTML = '<option value="">Selecione o motorista...</option>' + 
      resU.data.map(u => `<option value="${u.id}">${u.nome}</option>`).join('');
  }
}

async function carregarTabela() {
  const wrapper = document.getElementById('tabela-wrapper');
  wrapper.innerHTML = '<div class="empty-state">⏳ Carregando...</div>';

  const { ok, data } = await Api.getAbastecimentos();

  if (!ok || !Array.isArray(data) || data.length === 0) {
    wrapper.innerHTML = '<div class="empty-state">⛽ Nenhum abastecimento registrado.</div>';
    return;
  }

  const rows = data.map(a => `
    <tr>
      <td>${new Date(a.data_abastecimento).toLocaleDateString('pt-BR')}</td>
      <td><strong>${a.placa}</strong></td>
      <td>${a.motorista_nome}</td>
      <td>${a.km_atual} KM</td>
      <td>${a.litros} L</td>
      <td>R$ ${parseFloat(a.valor_total).toFixed(2)}</td>
      <td>${a.posto || '-'}</td>
      <td>
        <div style="display:flex; gap:8px;">
        ${Auth.get().perfil === 'admin' ? `
          <button class="btn-icon btn-delete" onclick="deletarAbastecimento(${a.id})" title="Excluir">🗑️</button>
        ` : ''}
      </div>
      </td>
    </tr>
  `).join('');

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
}

function abrirModal() {
  document.getElementById('modal-abastecimento').classList.add('active');
  document.getElementById('ab-data').value = new Date().toISOString().split('T')[0];
}

function fecharModal() {
  document.getElementById('modal-abastecimento').classList.remove('active');
  document.getElementById('form-abastecimento').reset();
}

async function excluir(id) {
  if (!confirm('Deseja excluir este registro de abastecimento?')) return;
  const { ok } = await Api.deleteAbastecimento(id);
  if (ok) {
    showToast('Registro excluído.', 'success');
    carregarTabela();
  }
}
