<?php
require_once 'functions.php';

verificarSessao();

$page_title = 'Dashboard';

require 'header.php';

$conn = db();

$conn = db();

/*
|--------------------------------------------------------------------------
| TOTAL DE VENDAS
|--------------------------------------------------------------------------
*/

$stmt = $conn->query("
    SELECT
        COALESCE(SUM(total),0)
    AS total
    FROM pedidos
    WHERE status = 'finalizado'
");

$total_vendas =
  $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| PEDIDOS HOJE
|--------------------------------------------------------------------------
*/

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM pedidos
    WHERE DATE(criado_em)
    = CURRENT_DATE
");

$pedidos_hoje =
  $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| PEDIDOS ABERTOS
|--------------------------------------------------------------------------
*/

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM pedidos
    WHERE status = 'aberto'
");

$pedidos_abertos =
  $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| MESAS
|--------------------------------------------------------------------------
*/

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM mesas
");

$total_mesas =
  $stmt->fetchColumn();

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM mesas
    WHERE status = 'ocupada'
");

$mesas_ocp =
  $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| CLIENTES
|--------------------------------------------------------------------------
*/

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM clientes
");

$total_cli =
  $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| VENDAS POR MÊS
|--------------------------------------------------------------------------
*/

$stmt = $conn->query("
    SELECT
        TO_CHAR(
            criado_em,
            'MM/YYYY'
        ) mes,

        COALESCE(
            SUM(total),
            0
        ) total

    FROM pedidos

    WHERE status =
    'finalizado'

    GROUP BY mes

    ORDER BY
    MIN(criado_em)
");

$vendas_mes =
  $stmt->fetchAll(
    PDO::FETCH_ASSOC
  );

$stmt = $conn->query("
SELECT
    p.*,
    c.nome AS cliente_nome,
    m.numero AS mesa_numero
FROM pedidos p
LEFT JOIN clientes c
ON c.id = p.cliente_id
LEFT JOIN mesas m
ON m.id = p.mesa_id
ORDER BY p.id DESC
LIMIT 10
");

$ultimos_pedidos =
  $stmt->fetchAll(
    PDO::FETCH_ASSOC
  );

$meses_labels =
  json_encode(
    array_column(
      $vendas_mes,
      'mes'
    )
  );

$meses_valores =
  json_encode(
    array_map(
      'floatval',
      array_column(
        $vendas_mes,
        'total'
      )
    )
  );

?>
<div class="row" style="margin-bottom:24px;">
  <div class="col-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(232,168,124,0.12);">💰</div>
      <div>
        <div class="stat-label">Total Vendas</div>
        <div class="stat-value"><?= formatarMoeda($total_vendas) ?></div>
        <div class="stat-sub">Todos os tempos</div>
      </div>
    </div>
  </div>
  <div class="col-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(91,155,213,0.12);">🧾</div>
      <div>
        <div class="stat-label">Pedidos Hoje</div>
        <div class="stat-value"><?= $pedidos_hoje ?></div>
        <div class="stat-sub"><?= $pedidos_abertos ?> em aberto</div>
      </div>
    </div>
  </div>
  <div class="col-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(224,92,92,0.12);">🪑</div>
      <div>
        <div class="stat-label">Mesas Ocupadas</div>
        <div class="stat-value"><?= $mesas_ocp ?>/<?= $total_mesas ?></div>
        <div class="stat-sub"><?= $total_mesas - $mesas_ocp ?> disponíveis</div>
      </div>
    </div>
  </div>
  <div class="col-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(92,184,92,0.12);">👥</div>
      <div>
        <div class="stat-label">Clientes</div>
        <div class="stat-value"><?= $total_cli ?></div>
        <div class="stat-sub">Cadastrados</div>
      </div>
    </div>
  </div>
</div>
<div class="row" style="margin-bottom:24px;">
  <div class="col-6">
    <div class="card">
      <div class="card-header">
        <h3>📈 Vendas por Mês</h3>
      </div>
      <div class="card-body">
        <canvas id="chartVendas" height="220"></canvas>
      </div>
    </div>
  </div>
  <div class="col-6">
    <div class="card">
      <div class="card-header">
        <h3>🪑 Status das Mesas</h3>
      </div>
      <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
          <?php

          $stmt = $conn->query("
    SELECT *
    FROM mesas
    ORDER BY numero
");

          $mesas =
            $stmt->fetchAll(
              PDO::FETCH_ASSOC
            );

          ?>

          <?php foreach ($mesas as $mesa): ?>

            <div style="
background:
<?= $mesa['status'] === 'livre'
              ? 'rgba(92,184,92,0.1)'
              : ($mesa['status'] === 'ocupada'
                ? 'rgba(224,92,92,0.1)'
                : 'rgba(230,168,23,0.1)') ?>;

border:1px solid
<?= $mesa['status'] === 'livre'
              ? 'rgba(92,184,92,0.3)'
              : ($mesa['status'] === 'ocupada'
                ? 'rgba(224,92,92,0.3)'
                : 'rgba(230,168,23,0.3)') ?>;

border-radius:12px;
padding:12px 16px;
text-align:center;
min-width:80px;
">

              <div style="font-size:1.4rem;">
                🪑
              </div>

              <div style="
font-size:0.8rem;
font-weight:700;
color:#e8e8f0;
">
                Mesa <?= $mesa['numero'] ?>
              </div>

              <div style="
font-size:0.68rem;
font-weight:600;
text-transform:capitalize;
color:
<?= $mesa['status'] === 'livre'
              ? '#5cb85c'
              : ($mesa['status'] === 'ocupada'
                ? '#e05c5c'
                : '#e6a817') ?>
">
                <?= $mesa['status'] ?>
              </div>

            </div>

          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-header">
    <h3>🧾 Últimos Pedidos</h3>
    <a href="pedidos.php" class="btn btn-secondary btn-sm">Ver Todos</a>
  </div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Mesa</th>
          <th>Cliente</th>
          <th>Total</th>
          <th>Status</th>
          <th>Data</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ultimos_pedidos)): ?>
          <tr>
            <td colspan="6" style="text-align:center;padding:24px;color:#888;">Nenhum pedido encontrado</td>
          </tr>
        <?php else: ?>
          <?php foreach ($ultimos_pedidos as $p): ?>
            <tr>
              <td>#<?= $p['id'] ?></td>
              <td><?= $p['mesa_numero'] ?></td>
              <td><?= htmlspecialchars($p['cliente_nome']) ?></td>
              <td style="color:#e8a87c;font-weight:700;"><?= formatarMoeda($p['total']) ?></td>
              <td><span class="badge">
                  <?= ucfirst($p['status']) ?>
                </span></td>
              <td style="color:#888;"><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
  const ctx = document.getElementById('chartVendas').getContext('2d');
  const labels = <?= $meses_labels ?>;
  const valores = <?= $meses_valores ?>;
  const grad = ctx.createLinearGradient(0, 0, 0, 220);
  grad.addColorStop(0, 'rgba(232,168,124,0.35)');
  grad.addColorStop(1, 'rgba(232,168,124,0)');
  const chart = {
    draw() {
      ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
      if (!labels.length) {
        ctx.fillStyle = '#888';
        ctx.font = '14px Segoe UI';
        ctx.textAlign = 'center';
        ctx.fillText('Sem dados ainda', ctx.canvas.width / 2, 110);
        return;
      }
      const W = ctx.canvas.width,
        H = ctx.canvas.height,
        pad = 40,
        bPad = 30;
      const max = Math.max(...valores, 1);
      const xStep = (W - pad * 2) / Math.max(labels.length - 1, 1);
      const yScale = (H - bPad - pad) / max;
      ctx.strokeStyle = 'rgba(255,255,255,0.05)';
      for (let i = 0; i <= 4; i++) {
        const y = pad + (H - bPad - pad) / 4 * i;
        ctx.beginPath();
        ctx.moveTo(pad, y);
        ctx.lineTo(W - pad, y);
        ctx.stroke();
      }
      const pts = labels.map((l, i) => ({
        x: pad + i * xStep,
        y: H - bPad - valores[i] * yScale
      }));
      ctx.beginPath();
      pts.forEach((p, i) => i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y));
      ctx.lineTo(pts[pts.length - 1].x, H - bPad);
      ctx.lineTo(pts[0].x, H - bPad);
      ctx.closePath();
      ctx.fillStyle = grad;
      ctx.fill();
      ctx.beginPath();
      pts.forEach((p, i) => i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y));
      ctx.strokeStyle = '#e8a87c';
      ctx.lineWidth = 2.5;
      ctx.stroke();
      pts.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, 5, 0, Math.PI * 2);
        ctx.fillStyle = '#e8a87c';
        ctx.fill();
      });
      ctx.fillStyle = '#666';
      ctx.font = '11px Segoe UI';
      ctx.textAlign = 'center';
      labels.forEach((l, i) => ctx.fillText(l, pad + i * xStep, H - 8));
    }
  };
  chart.draw();
  window.addEventListener('resize', () => chart.draw());
</script>
<?php require 'footer.php'; ?>