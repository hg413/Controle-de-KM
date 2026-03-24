// =============================================
// veiculos.js — CRUD de Veículos
// =============================================

function initVeiculos() {
  carregarVeiculos();

  document.getElementById('btn-novo-veiculo').addEventListener('click', () => {
    document.getElementById('modal-veiculo').classList.add('active');
  });

  document.getElementById('modal-fechar').addEventListener('click', fecharModalVeiculo);
  document.getElementById('btn-cancelar').addEventListener('click', fecharModalVeiculo);
  document.getElementById('modal-veiculo').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) fecharModalVeiculo();
  });

  document.getElementById('form-veiculo').addEventListener('submit', async (e) => {
    e.preventDefault();
    const placa      = document.getElementById('veiculo-placa').value.trim().toUpperCase();
    const motoristaId = document.getElementById('veiculo-motorista').value || null;

    const btn = document.getElementById('btn-salvar');
    btn.innerHTML = '<span class="spinner"></span>';
    btn.disabled = true;

    const { ok, data } = await Api.createVeiculo(placa, motoristaId);

    if (ok) {
      showToast(data.message || 'Veículo cadastrado!', 'success');
      fecharModalVeiculo();
      carregarVeiculos();
    } else {
      showToast(data.message || 'Erro ao cadastrar.', 'error');
    }

    btn.textContent = 'Cadastrar';
    btn.disabled = false;
  });
}

async function carregarVeiculos() {
  const wrapper = document.getElementById('tabela-wrapper');
  wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">⏳</div><p>Carregando...</p></div>';

  const [veiculosRes, usuariosRes] = await Promise.all([Api.getVeiculos(), Api.getUsuarios()]);

  // Popula select de motoristas no modal
  const motoristas = Array.isArray(usuariosRes.data)
    ? usuariosRes.data.filter(u => u.perfil === 'motorista')
    : [];

  const select = document.getElementById('veiculo-motorista');
  select.innerHTML = '<option value="">Nenhum</option>';
  motoristas.forEach(m => {
    const opt = document.createElement('option');
    opt.value = m.id;
    opt.textContent = m.nome;
    select.appendChild(opt);
  });

  const data = veiculosRes.data;
  if (!veiculosRes.ok || !Array.isArray(data) || data.length === 0) {
    wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">🚙</div><p>Nenhum veículo cadastrado.</p></div>';
    return;
  }

  const rows = data.map(v => `
    <tr>
      <td><strong>${v.placa}</strong></td>
      <td>${v.motorista_responsavel || '<span style="color:var(--text-secondary)">—</span>'}</td>
      <td>
        <button class="btn btn-danger btn-sm" onclick="excluirVeiculo(${v.id}, '${v.placa}')">🗑️ Excluir</button>
      </td>
    </tr>
  `).join('');

  wrapper.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Placa</th>
          <th>Motorista Responsável</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  `;
}

function fecharModalVeiculo() {
  document.getElementById('modal-veiculo').classList.remove('active');
  document.getElementById('form-veiculo').reset();
}

async function excluirVeiculo(id, placa) {
  if (!confirm(`Deseja excluir o veículo de placa "${placa}"?`)) return;

  const { ok, data } = await Api.deleteVeiculo(id);
  if (ok) {
    showToast(data.message || 'Veículo excluído.', 'success');
    carregarVeiculos();
  } else {
    showToast(data.message || 'Erro ao excluir.', 'error');
  }
}
