<?php
session_start();
require 'functions.php';
verificarSessao();
$page_title = 'Financeiro';
$db = lerDB();

$total_geral = 0;
$total_dinheiro = 0;
$total_pix = 0;
$total_cartao = 0;
$pagamentos_lista = array_reverse($db['pagamentos'] ?? []);

foreach (($db['pagamentos'] ?? []) as $pag) {
    $total_geral += $pag['valor'];
    if ($pag['forma'] === 'dinheiro') $total_dinheiro += $pag['valor'];
    elseif ($pag['forma'] === 'pix') $total_pix += $pag['valor'];
    elseif ($pag['forma'] === 'cartao') $total_cartao += $pag['valor'];
}

$filtro_forma = $_GET['forma'] ?? '';
if ($filtro_forma) {
    $pagamentos_lista = array_filter($db['pagamentos'] ?? [], fn($p) => $p['forma'] === $filtro_forma);
    $pagamentos_lista = array_reverse($pagamentos_lista);
}

$pedidos_fechados = count(array_filter($db['pedidos'] ?? [], fn($p) => $p['status'] === 'fechado'));
$ticket_medio = $pedidos_fechados > 0 ? $total_geral / $pedidos_fechados : 0;

require 'header.php';
?>
<div class="row" style="margin-bottom:24px;">
  <div class="col-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(232,168,124,0.12);">💰</div>
      <div><div class="stat-label">Receita Total</div><div class="stat-value" style="font-size:1.3rem;"><?= formatarMoeda($total_geral) ?></div></div>
    </div>
  </div>
  <div class="col-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(92,184,92,0.12);">💵</div>
      <div><div class="stat-label">Dinheiro</div><div class="stat-value" style="font-size:1.3rem;"><?= formatarMoeda($total_dinheiro) ?></div></div>
    </div>
  </div>
  <div class="col-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(91,155,213,0.12);">📱</div>
      <div><div class="stat-label">PIX</div><div class="stat-value" style="font-size:1.3rem;"><?= formatarMoeda($total_pix) ?></div></div>
    </div>
  </div>
  <div class="col-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(138,100,200,0.12);">💳</div>
      <div><div class="stat-label">Cartão</div><div class="stat-value" style="font-size:1.3rem;"><?= formatarMoeda($total_cartao) ?></div></div>
    </div>
  </div>
</div>

<div class="row" style="margin-bottom:24px;">
  <div class="col-6">
    <div class="card">
      <div class="card-header"><h3>📊 Distribuição por Forma</h3></div>
      <div class="card-body">
        <canvas id="chartFormas" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-6">
    <div class="card">
      <div class="card-header"><h3>📈 Resumo Financeiro</h3></div>
      <div class="card-body">
        <div style="display:grid;gap:14px;">
          <div style="display:flex;justify-content:space-between;padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;">
            <span style="color:#888;">Total de Vendas</span>
            <strong style="color:#e8a87c;"><?= formatarMoeda($total_geral) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;">
            <span style="color:#888;">Pedidos Fechados</span>
            <strong><?= $pedidos_fechados ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;">
            <span style="color:#888;">Ticket Médio</span>
            <strong style="color:#5cb85c;"><?= formatarMoeda($ticket_medio) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;">
            <span style="color:#888;">% Dinheiro</span>
            <strong><?= $total_geral > 0 ? number_format(($total_dinheiro/$total_geral)*100,1) : 0 ?>%</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;">
            <span style="color:#888;">% PIX</span>
            <strong><?= $total_geral > 0 ? number_format(($total_pix/$total_geral)*100,1) : 0 ?>%</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;">
            <span style="color:#888;">% Cartão</span>
            <strong><?= $total_geral > 0 ? number_format(($total_cartao/$total_geral)*100,1) : 0 ?>%</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>💳 Histórico de Pagamentos</h3>
    <div style="display:flex;gap:8px;">
      <a href="financeiro.php" class="btn btn-sm <?= !$filtro_forma ? 'btn-primary' : 'btn-secondary' ?>">Todos</a>
      <a href="financeiro.php?forma=dinheiro" class="btn btn-sm <?= $filtro_forma==='dinheiro' ? 'btn-primary' : 'btn-secondary' ?>">💵 Dinheiro</a>
      <a href="financeiro.php?forma=pix" class="btn btn-sm <?= $filtro_forma==='pix' ? 'btn-primary' : 'btn-secondary' ?>">📱 PIX</a>
      <a href="financeiro.php?forma=cartao" class="btn btn-sm <?= $filtro_forma==='cartao' ? 'btn-primary' : 'btn-secondary' ?>">💳 Cartão</a>
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>#</th><th>Pedido</th><th>Forma</th><th>Valor</th><th>Troco</th><th>Data/Hora</th></tr></thead>
      <tbody>
        <?php if (empty($pagamentos_lista)): ?>
        <tr><td colspan="6" style="text-align:center;padding:24px;color:#888;">Nenhum pagamento registrado</td></tr>
        <?php else: ?>
        <?php foreach ($pagamentos_lista as $pag): ?>
        <tr>
          <td><strong>#<?= $pag['id'] ?></strong></td>
          <td>Pedido #<?= $pag['pedido_id'] ?></td>
          <td>
            <?php
            $icones = ['dinheiro'=>'💵 Dinheiro','pix'=>'📱 PIX','cartao'=>'💳 Cartão'];
            echo $icones[$pag['forma']] ?? ucfirst($pag['forma']);
            ?>
          </td>
          <td style="color:#e8a87c;font-weight:700;"><?= formatarMoeda($pag['valor']) ?></td>
          <td><?= $pag['troco'] > 0 ? '<span style="color:#5cb85c;">' . formatarMoeda($pag['troco']) . '</span>' : '<span style="color:#888;">—</span>' ?></td>
          <td style="color:#888;"><?= date('d/m/Y H:i', strtotime($pag['criado_em'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function(){
  const canvas = document.getElementById('chartFormas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width, H = canvas.height;
  const dados = [
    {label:'Dinheiro', valor:<?= $total_dinheiro ?>, cor:'#5cb85c'},
    {label:'PIX', valor:<?= $total_pix ?>, cor:'#5b9bd5'},
    {label:'Cartão', valor:<?= $total_cartao ?>, cor:'#e8a87c'},
  ];
  const total = dados.reduce((s,d)=>s+d.valor,0);
  if (!total) {
    ctx.fillStyle='#888'; ctx.font='14px Segoe UI'; ctx.textAlign='center';
    ctx.fillText('Sem dados financeiros', W/2, H/2); return;
  }
  let angulo = -Math.PI/2;
  const cx=W/2, cy=H/2, r=Math.min(W,H)/2-20;
  dados.forEach(d=>{
    if (!d.valor) return;
    const fatia = (d.valor/total)*Math.PI*2;
    ctx.beginPath(); ctx.moveTo(cx,cy);
    ctx.arc(cx,cy,r,angulo,angulo+fatia);
    ctx.closePath(); ctx.fillStyle=d.cor; ctx.fill();
    ctx.strokeStyle='#141418'; ctx.lineWidth=3; ctx.stroke();
    const mid = angulo + fatia/2;
    const lx = cx + Math.cos(mid)*(r*0.65), ly = cy + Math.sin(mid)*(r*0.65);
    ctx.fillStyle='#fff'; ctx.font='bold 12px Segoe UI'; ctx.textAlign='center';
    if ((d.valor/total)>0.05) ctx.fillText(Math.round(d.valor/total*100)+'%', lx, ly);
    angulo += fatia;
  });
  const legY = H - 20;
  let legX = W/2 - 120;
  dados.forEach(d=>{
    ctx.fillStyle=d.cor; ctx.fillRect(legX,legY-10,12,12);
    ctx.fillStyle='#aaa'; ctx.font='11px Segoe UI'; ctx.textAlign='left';
    ctx.fillText(d.label, legX+16, legY); legX+=90;
  });
})();
</script>
<?php require 'footer.php'; ?>