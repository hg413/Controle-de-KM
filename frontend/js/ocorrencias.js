// =========================================================
// ocorrencias.js — Lógica do módulo de Ocorrências
// =========================================================

function initOcorrencias() {
  carregarOcorrencias();
  preencherSelectsOcorrencia();

  // Configuração do Modal
  const btnNovo = document.getElementById('btn-nova-ocorrencia');
  if (btnNovo) btnNovo.addEventListener('click', abrirModalOcorrencia);
  
  const btnFechar = document.getElementById('modal-fechar');
  if (btnFechar) btnFechar.addEventListener('click', fecharModalOcorrencia);
  
  const btnCancelar = document.getElementById('btn-cancelar');
  if (btnCancelar) btnCancelar.addEventListener('click', fecharModalOcorrencia);

  // Foto: converte arquivo para base64 e mostra preview
  document.getElementById('oc-foto').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('foto-preview');
    if (!file) { preview.style.display = 'none'; return; }
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });

  document.getElementById('form-ocorrencia').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-registrar');
    const originalText = btn.textContent;
    btn.textContent = 'Enviando...';
    btn.disabled = true;

    // Captura a foto como Base64 (se houver)
    let fotoB64 = null;
    const fotoInput = document.getElementById('oc-foto');
    if (fotoInput.files.length > 0) {
      fotoB64 = await new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target.result);
        reader.readAsDataURL(fotoInput.files[0]);
      });
    }

    const payload = {
      veiculo_id:      document.getElementById('oc-veiculo').value,
      motorista_id:    document.getElementById('oc-motorista').value,
      data_ocorrencia: document.getElementById('oc-data').value,
      hora_ocorrencia: document.getElementById('oc-hora').value,
      km_atual:        parseInt(document.getElementById('oc-km').value) || null,
      local_ocorrencia:document.getElementById('oc-local').value.trim(),
      descricao:       document.getElementById('oc-descricao').value.trim(),
      foto:            fotoB64
    };

    const res = await Api.createOcorrencia(payload);
    if (res.ok) {
      showToast(res.data.message || 'Ocorrência registrada!', 'success');
      fecharModalOcorrencia();
      carregarOcorrencias();
    } else {
      showToast(res.data.message || 'Erro ao registrar.', 'error');
    }

    btn.textContent = originalText;
    btn.disabled = false;
  });
}

function abrirModalOcorrencia() {
  document.getElementById('modal-ocorrencia').classList.add('active');
  // Data/hora atual como padrão ao abrir
  const d = new Date();
  document.getElementById('oc-data').value = d.toISOString().split('T')[0];
  document.getElementById('oc-hora').value = d.toTimeString().slice(0, 5);
}

function fecharModalOcorrencia() {
  document.getElementById('modal-ocorrencia').classList.remove('active');
  document.getElementById('form-ocorrencia').reset();
  document.getElementById('foto-preview').style.display = 'none';
}

async function preencherSelectsOcorrencia() {
  const [vRes, uRes] = await Promise.all([Api.getVeiculos(), Api.getUsuarios()]);
  const sVeiculo   = document.getElementById('oc-veiculo');
  const sMotorista = document.getElementById('oc-motorista');
  const me = Auth.get();

  if (vRes.ok) {
    sVeiculo.innerHTML = '<option value="">Selecione um veículo</option>' +
      vRes.data.map(v => `<option value="${v.id}">${v.placa}</option>`).join('');
  }
  if (uRes.ok) {
    sMotorista.innerHTML = '<option value="">Selecione o motorista</option>' +
      uRes.data.map(u => `<option value="${u.id}">${u.nome}</option>`).join('');
    if (me && me.perfil === 'motorista') {
      sMotorista.value = me.id;
      sMotorista.style.pointerEvents = 'none';
      sMotorista.style.background = 'var(--bg-hover)';
    }
  }
}

const statusLabel = {
  'aberta':     { text: 'Aberta',     cls: 'badge-danger'  },
  'em_analise': { text: 'Em Análise', cls: 'badge-warning' },
  'resolvida':  { text: 'Resolvida',  cls: 'badge-success' },
};

async function carregarOcorrencias() {
  const wrapper = document.getElementById('tabela-ocorrencias');
  wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">⏳</div><p>Carregando...</p></div>';

  const { ok, data } = await Api.getOcorrencias();
  const me = Auth.get();

  if (!ok || !Array.isArray(data) || data.length === 0) {
    wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><p>Nenhuma ocorrência registrada.</p></div>';
    return;
  }

  const rows = data.map(o => {
    const st = statusLabel[o.status] || statusLabel['aberta'];
    const fotoThumb = o.foto
      ? `<a href="${o.foto}" target="_blank"><img src="${o.foto}" alt="foto" style="height:32px;border-radius:6px;border:1px solid var(--border);"/></a>`
      : '<span style="color:var(--text-secondary)">—</span>';

    const statusOptions = me.perfil === 'admin'
      ? `<select class="status-select" onchange="alterarStatus(${o.id}, this.value)">
           <option value="aberta"     ${o.status==='aberta'     ? 'selected':''}>Aberta</option>
           <option value="em_analise" ${o.status==='em_analise' ? 'selected':''}>Em Análise</option>
           <option value="resolvida"  ${o.status==='resolvida'  ? 'selected':''}>Resolvida</option>
         </select>`
      : `<span class="badge ${st.cls}">${st.text}</span>`;

    return `
      <tr>
        <td>${new Date(o.data_ocorrencia).toLocaleDateString('pt-BR')}<br><small>${o.hora_ocorrencia.slice(0,5)}</small></td>
        <td><strong>${o.placa}</strong></td>
        <td>${o.motorista_nome}</td>
        <td>${o.local_ocorrencia || '—'}</td>
        <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${o.descricao}">${o.descricao}</td>
        <td>${fotoThumb}</td>
        <td>${statusOptions}</td>
        <td>
          ${me.perfil === 'admin' ? `<button class="btn-icon btn-delete" onclick="deletarOcorrencia(${o.id})" title="Excluir">🗑️</button>` : '—'}
        </td>
      </tr>`;
  }).join('');

  wrapper.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Data/Hora</th>
          <th>Veículo</th>
          <th>Motorista</th>
          <th>Local</th>
          <th>Descrição</th>
          <th>Foto</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>`;
}

async function alterarStatus(id, status) {
  const res = await Api.updateOcorrenciaStatus(id, status);
  if (res.ok) showToast('Status atualizado!', 'success');
  else showToast('Erro ao atualizar status.', 'error');
}

async function deletarOcorrencia(id) {
  if (!confirm('Deseja excluir esta ocorrência permanentemente?')) return;
  const { ok } = await Api.deleteOcorrencia(id);
  if (ok) { showToast('Ocorrência removida.', 'success'); carregarOcorrencias(); }
}
