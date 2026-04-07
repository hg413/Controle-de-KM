// =========================================================
// registro_diario.js — Lógica para o formulário de uso diário
// =========================================================

let canvas, ctx;
let isDrawing = false;
let hasSignature = false;

// Função principal de inicialização da tela
function initRegistroDiario() {
  setupCanvas();
  carregarSelects();
  carregarTabela();

  // Escuta as mudanças de input para recalcular KM dinâmico
  const kmIniInput = document.getElementById('rd-km-inicial');
  const kmFimInput = document.getElementById('rd-km-final');
  if (kmIniInput && kmFimInput) {
    const kmDisplay  = document.getElementById('rd-km-rodado');       // span
    const kmHidden   = document.getElementById('rd-km-rodado-val');   // input hidden
    const kmWrapper  = document.getElementById('rd-km-rodado-wrapper');

    const calcKM = () => {
      let i = parseInt(kmIniInput.value) || 0;
      let f = parseInt(kmFimInput.value) || 0;
      let saldo = Math.max(0, f - i);
      kmDisplay.textContent = saldo > 0 ? `${saldo} km` : '0 km';
      kmHidden.value = saldo;
      if (saldo > 0) {
        kmWrapper.classList.add('has-value');
      } else {
        kmWrapper.classList.remove('has-value');
      }
    };
    kmIniInput.addEventListener('input', calcKM);
    kmFimInput.addEventListener('input', calcKM);
  }

  // Lógica de toggle para Contrato/Avulso
  const selectTipo = document.getElementById('rd-tipo-viagem');
  const containerContrato = document.getElementById('container-contrato');
  if (selectTipo && containerContrato) {
    selectTipo.addEventListener('change', () => {
      containerContrato.style.display = selectTipo.value === 'contrato' ? 'block' : 'none';
    });
  }

  // Escuta o botão limpar assinatura
  const btnLimpar = document.getElementById('limpar-assinatura');
  if (btnLimpar) btnLimpar.addEventListener('click', limparCanvas);

  // Submissão do form
  const form = document.getElementById('form-registro-diario');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (!hasSignature) {
        showToast('Por favor, assine o documento.', 'error');
        return;
      }

      const btn = document.getElementById('btn-salvar');
      btn.innerHTML = '<span class="spinner"></span> Processando...';
      btn.disabled = true;

      // Converte a canvas pra PNG Base64
      const assinaturaB64 = canvas.toDataURL("image/png");

      let kmInicialVal = parseInt(document.getElementById('rd-km-inicial').value) || 0;
      let kmFinalVal = parseInt(document.getElementById('rd-km-final').value) || 0;
      let kmRodadoVal = parseInt(document.getElementById('rd-km-rodado-val').value) || 0;

      const payload = {
        veiculo_id: document.getElementById('rd-veiculo').value,
        motorista_id: document.getElementById('rd-motorista').value,
        tipo_viagem: document.getElementById('rd-tipo-viagem')?.value || 'avulso',
        contrato_id: document.getElementById('rd-contrato')?.value || null,
        data_registro: document.getElementById('rd-data').value,
        hora_inicio: document.getElementById('rd-hora-inicio').value,
        hora_final: document.getElementById('rd-hora-final').value,
        km_inicial: kmInicialVal,
        km_final: kmFinalVal,
        km_rodado: kmRodadoVal,
        destino_motivo: document.getElementById('rd-motivo').value.trim(),
        assinatura_digital: assinaturaB64
      };

      const res = await Api.createRegistroDiario(payload);
      if (res.ok) {
          showToast(res.data.message || 'Registro efetuado!', 'success');
          form.reset();
          limparCanvas();
          if (containerContrato) containerContrato.style.display = 'none';
          carregarTabela();
      } else {
          showToast(res.data.message || 'Erro ao registrar.', 'error');
      }

      btn.textContent = 'Salvar Registro';
      btn.disabled = false;
    });
  }

  // Data default hoje e Hora default sugerida
  const inputData = document.getElementById('rd-data');
  const inputHora = document.getElementById('rd-hora-inicio');
  if (inputData && inputHora) {
    const d = new Date();
    inputData.value = d.toISOString().split('T')[0];
    inputHora.value = d.toTimeString().slice(0,5);
  }
}

// Inicializa lógica visual de desenho (com suporte a toque e mouse)
function setupCanvas() {
  canvas = document.getElementById('assinatura');
  if (!canvas) return;
  ctx = canvas.getContext('2d');
  
  // Ajusta a resolução natural do canvas pro tamanho real do CSS para evitar traço borrado
  const rect = canvas.parentElement.getBoundingClientRect();
  canvas.width = rect.width;
  canvas.height = 150; 
  
  // Estilo do traço (semelhante à caneta azul/preta fina)
  ctx.lineWidth = 2.5;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.strokeStyle = '#003366'; // Azul marinho
  
  // Helpers para lidar com coordenadas touch e mouse igualmente
  const getPos = (e) => {
    let clientX = e.clientX;
    let clientY = e.clientY;
    if (e.touches && e.touches.length > 0) {
      clientX = e.touches[0].clientX;
      clientY = e.touches[0].clientY;
    }
    const bcr = canvas.getBoundingClientRect();
    return {
      x: clientX - bcr.left,
      y: clientY - bcr.top
    };
  };

  const startDraw = (e) => {
    e.preventDefault(); // previne scroll da tela no celular ao assinar
    isDrawing = true;
    hasSignature = true;
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
  };
  
  const draw = (e) => {
    if (!isDrawing) return;
    e.preventDefault();
    const pos = getPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
  };
  
  const endDraw = (e) => {
    if(!isDrawing) return;
    e.preventDefault();
    isDrawing = false;
  };

  canvas.addEventListener('mousedown', startDraw);
  canvas.addEventListener('mousemove', draw);
  window.addEventListener('mouseup', endDraw); // Janela escuta pra parar se usuario sair do quadrado cortando o clique
  
  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove', draw, { passive: false });
  window.addEventListener('touchend', endDraw);
}

function limparCanvas() {
  if (!canvas) return;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  hasSignature = false;
}

// Carrega listas suspensas
async function carregarSelects() {
  const [vRes, uRes, cRes] = await Promise.all([
    Api.getVeiculos(), 
    Api.getUsuarios(),
    Api.getContratos()
  ]);

  const sVeiculo = document.getElementById('rd-veiculo');
  const sMotorista = document.getElementById('rd-motorista');
  const sContrato = document.getElementById('rd-contrato');
  const me = Auth.get(); // Usuário logado atual
  
  if (vRes.ok && sVeiculo) {
    sVeiculo.innerHTML = '<option value="">Selecione um veículo</option>' + 
      vRes.data.map(v => `<option value="${v.id}">${v.placa}</option>`).join('');
  }
  
  if (uRes.ok && sMotorista) {
    let users = uRes.data;
    sMotorista.innerHTML = '<option value="">Quem está com o veículo?</option>' + 
       users.map(u => `<option value="${u.id}">${u.nome}</option>`).join('');
       
    if(me && me.perfil === 'motorista') {
      sMotorista.value = me.id;
      sMotorista.style.pointerEvents = "none";
      sMotorista.style.background = "#f0f0f0";
    }
  }

  if (cRes.ok && sContrato) {
    sContrato.innerHTML = '<option value="">Selecione um contrato</option>' + 
      cRes.data.map(c => `<option value="${c.id}">${c.nome} - ${c.cliente}</option>`).join('');
  }
}

// Lista histórico
async function carregarTabela() {
  const wrapper = document.getElementById('tabela-wrapper');
  if (!wrapper) return;
  wrapper.innerHTML = '<div class="empty-state">⏳ Carregando registros...</div>';
  const { ok, data } = await Api.getRegistrosDiarios();

  if (!ok || !Array.isArray(data) || data.length === 0) {
    wrapper.innerHTML = '<div class="empty-state"><div class="empty-icon">📂</div><p>Nenhum histórico diário gerado.</p></div>';
    return;
  }
  
  const me = Auth.get();

  // Mapeia e cria UI das tabelas
  const rows = data.map(r => `
    <tr>
      <td>${new Date(r.data_registro).toLocaleDateString('pt-BR')} <br/><small>${r.hora_inicio.slice(0,5)} as ${r.hora_final.slice(0,5)}</small></td>
      <td><strong>${r.placa}</strong></td>
      <td>${r.motorista_nome}</td>
      <td>
        <span class="badge ${r.tipo_viagem === 'contrato' ? 'badge-blue' : 'badge-gray'}">
          ${r.tipo_viagem === 'contrato' ? '📄 Contrato' : '🚗 Avulso'}
        </span>
        ${r.contrato_nome ? `<br/><small>${r.contrato_nome}</small>` : ''}
      </td>
      <td>${r.km_rodado} km</td>
      <td>
        <a href="${r.assinatura_digital}" target="_blank" title="Ver original">
           <img src="${r.assinatura_digital}" alt="Assinatura" style="height:32px; border:1px solid #ddd; background:white; border-radius:4px;"/>
        </a>
      </td>
      <td>
        ${me.perfil === 'admin' ? `
          <button class="btn-icon btn-delete" onclick="deletarRegistro(${r.id})" title="Excluir">🗑️</button>
        ` : '-'}
      </td>
    </tr>
  `).join('');

  wrapper.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>Período</th>
          <th>Veículo</th>
          <th>Motorista</th>
          <th>Tipo</th>
          <th>Rodagem</th>
          <th>Assinatura</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  `;
}

// Admin: função extra para excluir logs manuais pra limpeza
async function deletarRegistro(id) {
  if(!confirm('Cuidado, está apagando um documento formalizado. A assinatura anexada será descartada. Confirma deleção?')) return;
  const { ok } = await Api.deleteRegistroDiario(id);
  if (ok) {
    showToast('Documento removido.', 'success');
    carregarTabela();
  }
}

// Admin: função extra para excluir logs manuais pra limpeza
async function deletarRegistro(id) {
  if(!confirm('Cuidado, está apagando um documento formalizado. A assinatura anexada será descartada. Confirma deleção?')) return;
  const { ok } = await Api.deleteRegistroDiario(id);
  if (ok) {
    showToast('Documento removido.', 'success');
    carregarTabela();
  }
}
