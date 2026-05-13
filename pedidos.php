<?php
session_start();
require 'functions.php';
verificarSessao();
$page_title = 'Pedidos';
$conn = db();
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';
  if ($acao === 'novo_pedido') {

    $mesa_id =
      intval($_POST['mesa_id'] ?? 0);

    $cliente_id =
      intval($_POST['cliente_id'] ?? 0);

    $obs =
      trim($_POST['observacao'] ?? '');

    if (!$mesa_id) {

      $erro =
        'Selecione uma mesa.';
    } else {

      try {

        $conn->beginTransaction();

        $stmt =
          $conn->prepare("
                INSERT INTO pedidos
                (
                    mesa_id,
                    cliente_id,
                    observacao,
                    status,
                    total,
                    criado_em
                )
                VALUES
                (
                    :mesa_id,
                    :cliente_id,
                    :obs,
                    'aberto',
                    0,
                    NOW()
                )
                RETURNING id
            ");

        $stmt->execute([
          ':mesa_id'
          => $mesa_id,

          ':cliente_id'
          => $cliente_id ?: null,

          ':obs'
          => $obs
        ]);

        $pedido_id =
          $stmt->fetchColumn();

        $stmt =
          $conn->prepare("
                UPDATE mesas
                SET status =
                'ocupada'
                WHERE id = :id
            ");

        $stmt->execute([
          ':id' => $mesa_id
        ]);

        $conn->commit();

        $msg =
          "Pedido #{$pedido_id} aberto!";
      } catch (Exception $e) {

        $conn->rollBack();

        $erro =
          $e->getMessage();
      }
    }           // $db = lerDB();
  }elseif ($acao === 'add_item') {

    $pedido_id =
    intval($_POST['pedido_id'] ?? 0);

    $produto_id =
    intval($_POST['produto_id'] ?? 0);

    $qtd =
    intval($_POST['quantidade'] ?? 1);

    if (
        $pedido_id <= 0
        || $produto_id <= 0
        || $qtd <= 0
    ) {

        $erro =
        'Dados inválidos.';

    } else {

        try {

            $stmt =
            $conn->prepare("
                SELECT
                    id,
                    nome,
                    preco
                FROM produtos
                WHERE id = :id
                LIMIT 1
            ");

            $stmt->execute([
                ':id'
                => $produto_id
            ]);

            $produto =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$produto) {

                throw new Exception(
                    'Produto não encontrado.'
                );
            }

            $subtotal =
            $produto['preco']
            * $qtd;

            $stmt =
            $conn->prepare("
                INSERT INTO pedido_itens
                (
                    pedido_id,
                    produto_id,
                    quantidade,
                    preco_unit,
                    subtotal
                )
                VALUES
                (
                    :pedido_id,
                    :produto_id,
                    :qtd,
                    :preco,
                    :subtotal
                )
            ");

            $stmt->execute([

                ':pedido_id'
                => $pedido_id,

                ':produto_id'
                => $produto_id,

                ':qtd'
                => $qtd,

                ':preco'
                => $produto['preco'],

                ':subtotal'
                => $subtotal
            ]);

            $stmt =
            $conn->prepare("
                UPDATE pedidos
                SET total = (
                    SELECT
                    COALESCE(
                        SUM(subtotal),
                        0
                    )
                    FROM pedido_itens
                    WHERE pedido_id =
                    :pedido_id
                )
                WHERE id =
                :pedido_id
            ");

            $stmt->execute([
                ':pedido_id'
                => $pedido_id
            ]);

            $msg =
            'Item adicionado!';

        } catch(Exception $e) {

            $erro =
            $e->getMessage();
        }
    }
  } elseif ($acao === 'rem_item') {
    $pedido_id = intval($_POST['pedido_id'] ?? 0);
    $produto_id = intval($_POST['produto_id'] ?? 0);
    $db = lerDB();
    foreach ($db['pedidos'] as &$ped) {
      if ($ped['id'] == $pedido_id) {
        $ped['itens'] = array_values(array_filter($ped['itens'], fn($i) => $i['produto_id'] != $produto_id));
        $total = 0;
        foreach ($ped['itens'] as $it) $total += $it['subtotal'];
        $ped['total'] = round($total, 2);
        break;
      }
    }
    salvarDB($db);
    $db = lerDB();
    $msg = 'Item removido!';
  } elseif ($acao === 'status') {
    $pedido_id = intval($_POST['pedido_id'] ?? 0);
    $novo_status = $_POST['novo_status'] ?? '';
    $db = lerDB();
    foreach ($db['pedidos'] as &$ped) {
      if ($ped['id'] == $pedido_id) {
        $ped['status'] = $novo_status;
        if ($novo_status === 'fechado') $ped['fechado_em'] = dataHoraAtual();
        if (in_array($novo_status, ['fechado', 'cancelado'])) {
          $mesa_id = $ped['mesa_id'];
          foreach ($db['mesas'] as &$m) {
            if ($m['id'] == $mesa_id) {
              $m['status'] = 'livre';
              break;
            }
          }
        }
        break;
      }
    }
    salvarDB($db);
    $db = lerDB();
    $msg = 'Status atualizado!';
  } elseif ($acao === 'fechar_pagar') {
    $pedido_id = intval($_POST['pedido_id'] ?? 0);
    $forma = $_POST['forma_pagamento'] ?? 'dinheiro';
    $valor_pago = floatval(str_replace(',', '.', $_POST['valor_pago'] ?? 0));
    $db = lerDB();
    $total_ped = 0;
    $mesa_id_ped = 0;
    foreach ($db['pedidos'] as &$ped) {
      if ($ped['id'] == $pedido_id) {
        $total_ped = $ped['total'];
        $mesa_id_ped = $ped['mesa_id'];
        $ped['status'] = 'fechado';
        $ped['fechado_em'] = dataHoraAtual();
        break;
      }
    }
    foreach ($db['mesas'] as &$m) {
      if ($m['id'] == $mesa_id_ped) {
        $m['status'] = 'livre';
        break;
      }
    }
    $novo_pag_id = proximoId('pagamentos');
    $db = lerDB();
    $db['pagamentos'][] = ['id' => $novo_pag_id, 'pedido_id' => $pedido_id, 'forma' => $forma, 'valor' => $total_ped, 'troco' => max(0, $valor_pago - $total_ped), 'criado_em' => dataHoraAtual()];
    foreach ($db['pedidos'] as &$ped) {
      if ($ped['id'] == $pedido_id) {
        $ped['status'] = 'fechado';
        $ped['fechado_em'] = dataHoraAtual();
        break;
      }
    }
    foreach ($db['mesas'] as &$m) {
      if ($m['id'] == $mesa_id_ped) {
        $m['status'] = 'livre';
        break;
      }
    }
    salvarDB($db);
    $db = lerDB();
    $msg = 'Pedido fechado e pagamento registrado!';
  }
}
$filtro_status = $_GET['status'] ?? '';
$sql = "
SELECT
    p.*,
    c.nome AS cliente_nome,
    m.numero AS mesa_numero,

    (
        SELECT COUNT(*)
        FROM pedido_itens pi
        WHERE pi.pedido_id = p.id
    ) AS total_itens

FROM pedidos p

LEFT JOIN clientes c
ON c.id = p.cliente_id

LEFT JOIN mesas m
ON m.id = p.mesa_id
";

$params = [];

if ($filtro_status) {

  $sql .= "
    WHERE p.status = :status
    ";

  $params[':status']
    = $filtro_status;
}

$sql .= "
ORDER BY p.id DESC
";

$stmt =
  $conn->prepare($sql);

$stmt->execute($params);

$pedidos =
  $stmt->fetchAll(
    PDO::FETCH_ASSOC
  );

$ver_pedido = null;

if (isset($_GET['ver'])) {

  $stmt =
    $conn->prepare("
        SELECT
            p.*,
            c.nome AS cliente_nome,
            m.numero AS mesa_numero
        FROM pedidos p
        LEFT JOIN clientes c
        ON c.id = p.cliente_id
        LEFT JOIN mesas m
        ON m.id = p.mesa_id
        WHERE p.id = :id
        LIMIT 1
    ");

  $stmt->execute([
    ':id' =>
    intval($_GET['ver'])
  ]);

  $ver_pedido =
    $stmt->fetch(
      PDO::FETCH_ASSOC
    );

    if ($ver_pedido) {

    $stmt =
    $conn->prepare("
        SELECT
            pi.*,
            p.nome
        FROM pedido_itens pi
        INNER JOIN produtos p
        ON p.id = pi.produto_id
        WHERE pi.pedido_id = :id
        ORDER BY pi.id
    ");

    $stmt->execute([
        ':id'
        => $ver_pedido['id']
    ]);

    $ver_pedido['itens'] =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );
}
}

$stmt = $conn->query("
SELECT *
FROM mesas
ORDER BY numero
");

$mesas =
  $stmt->fetchAll(
    PDO::FETCH_ASSOC
  );

$stmt = $conn->query("
SELECT *
FROM clientes
ORDER BY nome
");

$clientes =
  $stmt->fetchAll(
    PDO::FETCH_ASSOC
  );

$stmt = $conn->query("
SELECT *
FROM produtos
WHERE ativo = true
ORDER BY nome
");

$produtos =
$stmt->fetchAll(
    PDO::FETCH_ASSOC
);  

require 'header.php';
?>
<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($erro) ?></div><?php endif; ?>

<?php if ($ver_pedido): ?>
  <div class="row" style="margin-bottom:20px;">
    <div class="col-6">
      <div class="card">
        <div class="card-header">
          <h3>
            📋 Pedido #<?= $ver_pedido['id'] ?>
            — Mesa <?= $ver_pedido['mesa_numero'] ?>
          </h3>
          <a href="pedidos.php" class="btn btn-secondary btn-sm">← Voltar</a>
        </div>
        <div class="card-body">
          <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:16px;">
            <div><span style="color:#888;font-size:0.78rem;">STATUS</span><br><span class="badge">
                <?= ucfirst(
                  str_replace(
                    '_',
                    ' ',
                    $ver_pedido['status']
                  )
                ) ?>
              </span></div>
            <div><span style="color:#888;font-size:0.78rem;">CLIENTE</span><br><strong><?= htmlspecialchars(
                                                                                          $ver_pedido['cliente_nome']
                                                                                            ?? 'Não identificado'
                                                                                        ) ?></strong></div>
            <div><span style="color:#888;font-size:0.78rem;">ABERTO EM</span><br><strong><?= date('d/m/Y H:i', strtotime($ver_pedido['criado_em'])) ?></strong></div>
            <div><span style="color:#888;font-size:0.78rem;">TOTAL</span><br><strong style="color:#e8a87c;font-size:1.2rem;"><?= formatarMoeda($ver_pedido['total']) ?></strong></div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Produto</th>
                <th>Qtd</th>
                <th>Unitário</th>
                <th>Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($ver_pedido['itens'])): ?>
                <tr>
                  <td colspan="5" style="text-align:center;color:#888;padding:16px;">Nenhum item</td>
                </tr>
              <?php else: ?>
                <?php foreach ($ver_pedido['itens'] as $it): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($it['nome']) ?></strong></td>
                    <td><?= $it['quantidade'] ?></td>
                    <td><?= formatarMoeda($it['preco_unit']) ?></td>
                    <td style="color:#e8a87c;font-weight:700;"><?= formatarMoeda($it['subtotal']) ?></td>
                    <td>
                      <?php if ($ver_pedido['status'] === 'aberto'): ?>
                        <form method="POST" action="pedidos.php?ver=<?= $ver_pedido['id'] ?>" style="display:inline;">
                          <input type="hidden" name="acao" value="rem_item">
                          <input type="hidden" name="pedido_id" value="<?= $ver_pedido['id'] ?>">
                          <input type="hidden" name="produto_id" value="<?= $it['produto_id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm">✖</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
          <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <?php if ($ver_pedido['status'] === 'aberto'): ?>
              <form method="POST" action="pedidos.php?ver=<?= $ver_pedido['id'] ?>">
                <input type="hidden" name="acao" value="status">
                <input type="hidden" name="pedido_id" value="<?= $ver_pedido['id'] ?>">
                <input type="hidden" name="novo_status" value="em_preparo">
                <button class="btn btn-info btn-sm">🍳 Em Preparo</button>
              </form>
            <?php elseif ($ver_pedido['status'] === 'em_preparo'): ?>
              <form method="POST" action="pedidos.php?ver=<?= $ver_pedido['id'] ?>">
                <input type="hidden" name="acao" value="status">
                <input type="hidden" name="pedido_id" value="<?= $ver_pedido['id'] ?>">
                <input type="hidden" name="novo_status" value="pronto">
                <button class="btn btn-primary btn-sm">✅ Pronto</button>
              </form>
            <?php endif; ?>
            <?php if (in_array($ver_pedido['status'], ['aberto', 'em_preparo', 'pronto'])): ?>
              <form method="POST" action="pedidos.php?ver=<?= $ver_pedido['id'] ?>">
                <input type="hidden" name="acao" value="status">
                <input type="hidden" name="pedido_id" value="<?= $ver_pedido['id'] ?>">
                <input type="hidden" name="novo_status" value="cancelado">
                <button class="btn btn-danger btn-sm" onclick="return confirm('Cancelar pedido?')">🚫 Cancelar</button>
              </form>
              <button class="btn btn-success btn-sm" onclick="document.getElementById('modal-pagar').classList.add('show')">💳 Fechar e Pagar</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6" style="max-width:340px;">
      <?php if ($ver_pedido['status'] === 'aberto'): ?>
        <div class="card">
          <div class="card-header">
            <h3>➕ Adicionar Item</h3>
          </div>
          <div class="card-body">
            <form method="POST" action="pedidos.php?ver=<?= $ver_pedido['id'] ?>">
              <input type="hidden" name="acao" value="add_item">
              <input type="hidden" name="pedido_id" value="<?= $ver_pedido['id'] ?>">
              <div class="form-group">
                <label>Produto</label>
                <select name="produto_id" class="form-control">
                  <?php foreach ($produtos as $prod): ?>
                    <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['nome']) ?> — <?= formatarMoeda($prod['preco']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Quantidade</label>
                <input type="number" name="quantidade" min="1" value="1" class="form-control">
              </div>
              <button type="submit" class="btn btn-primary w-100">➕ Adicionar</button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="modal-overlay" id="modal-pagar">
    <div class="modal-box">
      <h3>💳 Fechar Pedido #<?= $ver_pedido['id'] ?></h3>
      <p style="color:#888;margin-bottom:16px;">Total: <strong style="color:#e8a87c;font-size:1.2rem;"><?= formatarMoeda($ver_pedido['total']) ?></strong></p>
      <form method="POST" action="pedidos.php?ver=<?= $ver_pedido['id'] ?>">
        <input type="hidden" name="acao" value="fechar_pagar">
        <input type="hidden" name="pedido_id" value="<?= $ver_pedido['id'] ?>">
        <div class="form-group">
          <label>Forma de Pagamento</label>
          <select name="forma_pagamento" class="form-control">
            <option value="dinheiro">💵 Dinheiro</option>
            <option value="pix">📱 PIX</option>
            <option value="cartao">💳 Cartão</option>
          </select>
        </div>
        <div class="form-group">
          <label>Valor Pago (R$)</label>
          <input type="number" step="0.01" min="0" name="valor_pago" class="form-control" value="<?= number_format($ver_pedido['total'], 2, '.', '.') ?>">
        </div>
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn btn-success">✅ Confirmar Pagamento</button>
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-pagar').classList.remove('show')">✖ Cancelar</button>
        </div>
      </form>
    </div>
  </div>

<?php else: ?>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="pedidos.php" class="btn btn-sm <?= !$filtro_status ? 'btn-primary' : 'btn-secondary' ?>">Todos</a>
      <a href="pedidos.php?status=aberto" class="btn btn-sm <?= $filtro_status === 'aberto' ? 'btn-primary' : 'btn-secondary' ?>">Abertos</a>
      <a href="pedidos.php?status=em_preparo" class="btn btn-sm <?= $filtro_status === 'em_preparo' ? 'btn-primary' : 'btn-secondary' ?>">Em Preparo</a>
      <a href="pedidos.php?status=pronto" class="btn btn-sm <?= $filtro_status === 'pronto' ? 'btn-primary' : 'btn-secondary' ?>">Prontos</a>
      <a href="pedidos.php?status=fechado" class="btn btn-sm <?= $filtro_status === 'fechado' ? 'btn-primary' : 'btn-secondary' ?>">Fechados</a>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modal-novo').classList.add('show')">➕ Novo Pedido</button>
  </div>
  <div class="card">
    <div class="card-header">
      <h3>🧾 Lista de Pedidos</h3>
    </div>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Mesa</th>
            <th>Cliente</th>
            <th>Itens</th>
            <th>Total</th>
            <th>Status</th>
            <th>Data</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pedidos)): ?>
            <tr>
              <td colspan="8" style="text-align:center;padding:24px;color:#888;">Nenhum pedido encontrado</td>
            </tr>
          <?php else: ?>
            <?php foreach ($pedidos as $ped): ?>
              <tr>
                <td><strong>#<?= $ped['id'] ?></strong></td>
                <td><?= 'Mesa ' . $ped['mesa_numero'] ?></td>
                <td><?= htmlspecialchars($ped['cliente_nome'] ?? 'Não identificado') ?></td>
                <td><span class="badge badge-info"><?= $ped['total_itens'] ?></span></td>
                <td style="color:#e8a87c;font-weight:700;"><?= formatarMoeda($ped['total']) ?></td>
                <td><span class="badge">
                    <?= ucfirst(
                      str_replace(
                        '_',
                        ' ',
                        $ped['status']
                      )
                    ) ?>
                  </span></td>
                <td style="color:#888;"><?= date('d/m/Y H:i', strtotime($ped['criado_em'])) ?></td>
                <td><a href="pedidos.php?ver=<?= $ped['id'] ?>" class="btn btn-info btn-sm">👁️ Ver</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal-overlay" id="modal-novo">
    <div class="modal-box">
      <h3>🧾 Novo Pedido</h3>
      <form method="POST" action="pedidos.php">
        <input type="hidden" name="acao" value="novo_pedido">
        <div class="form-group">
          <label>Mesa *</label>
          <select name="mesa_id" class="form-control" required>
            <option value="">Selecione uma mesa...</option>
            <?php foreach ($mesas as $m): ?>
              <option value="<?= $m['id'] ?>"><?= 'Mesa ' . $m['numero'] ?> — <?= htmlspecialchars($m['localizacao']) ?> (<?= $m['status'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Cliente</label>
          <select name="cliente_id" class="form-control">
            <option value="0">Não identificado</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Observação</label>
          <textarea name="observacao" class="form-control" placeholder="Alguma observação especial..."></textarea>
        </div>
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn btn-primary">🧾 Abrir Pedido</button>
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-novo').classList.remove('show')">✖ Cancelar</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>
<?php require 'footer.php'; ?>