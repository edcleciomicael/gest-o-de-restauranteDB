<?php
session_start();
require 'functions.php';
verificarSessao();
$page_title = 'Mesas';
$db = lerDB();
$msg = ''; $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'salvar') {
        $id = intval($_POST['id'] ?? 0);
        $numero = intval($_POST['numero'] ?? 0);
        $capacidade = intval($_POST['capacidade'] ?? 2);
        $localizacao = trim($_POST['localizacao'] ?? '');
        $status = $_POST['status'] ?? 'livre';
        if (!$numero) { $erro = 'Número é obrigatório.'; }
        else {
            $db = lerDB();
            if ($id > 0) {
                foreach ($db['mesas'] as &$m) {
                    if ($m['id'] == $id) { $m['numero']=$numero; $m['capacidade']=$capacidade; $m['localizacao']=$localizacao; $m['status']=$status; break; }
                }
                $msg = 'Mesa atualizada!';
            } else {
                $novo_id = proximoId('mesas');
                $db = lerDB();
                $db['mesas'][] = ['id'=>$novo_id,'numero'=>$numero,'capacidade'=>$capacidade,'status'=>'livre','localizacao'=>$localizacao];
                $msg = 'Mesa cadastrada!';
            }
            salvarDB($db);
            $db = lerDB();
        }
    } elseif ($acao === 'excluir') {
        $id = intval($_POST['id'] ?? 0);
        $db = lerDB();
        $db['mesas'] = array_values(array_filter($db['mesas'], fn($m) => $m['id'] != $id));
        salvarDB($db);
        $db = lerDB();
        $msg = 'Mesa excluída!';
    } elseif ($acao === 'status') {
        $id = intval($_POST['id'] ?? 0);
        $novo_status = $_POST['novo_status'] ?? 'livre';
        $db = lerDB();
        foreach ($db['mesas'] as &$m) {
            if ($m['id'] == $id) { $m['status'] = $novo_status; break; }
        }
        salvarDB($db);
        $db = lerDB();
        $msg = 'Status da mesa atualizado!';
    }
}

$edit = null;
if (isset($_GET['editar'])) $edit = buscarPorId('mesas', intval($_GET['editar']));
require 'header.php';
?>
<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($erro) ?></div><?php endif; ?>
<div class="row" style="margin-bottom:24px;">
  <div class="col-6" style="max-width:360px;">
    <div class="card">
      <div class="card-header"><h3><?= $edit ? '✏️ Editar Mesa' : '➕ Nova Mesa' ?></h3></div>
      <div class="card-body">
        <form method="POST" action="mesas.php">
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="id" value="<?= $edit ? $edit['id'] : 0 ?>">
          <div class="form-group"><label>Número *</label><input type="number" name="numero" class="form-control" required value="<?= $edit['numero'] ?? '' ?>"></div>
          <div class="form-group"><label>Capacidade</label><input type="number" name="capacidade" min="1" class="form-control" value="<?= $edit['capacidade'] ?? 4 ?>"></div>
          <div class="form-group"><label>Localização</label><input type="text" name="localizacao" class="form-control" value="<?= htmlspecialchars($edit['localizacao'] ?? '') ?>"></div>
          <?php if ($edit): ?>
          <div class="form-group"><label>Status</label>
            <select name="status" class="form-control">
              <option value="livre" <?= ($edit['status']??'livre')==='livre'?'selected':'' ?>>Livre</option>
              <option value="ocupada" <?= ($edit['status']??'')==='ocupada'?'selected':'' ?>>Ocupada</option>
              <option value="reservada" <?= ($edit['status']??'')==='reservada'?'selected':'' ?>>Reservada</option>
            </select>
          </div>
          <?php endif; ?>
          <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><?= $edit ? '💾 Atualizar' : '➕ Cadastrar' ?></button>
            <?php if ($edit): ?><a href="mesas.php" class="btn btn-secondary">✖ Cancelar</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-6">
    <div class="card">
      <div class="card-header"><h3>🪑 Mapa de Mesas</h3></div>
      <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:14px;">
          <?php foreach (($db['mesas'] ?? []) as $mesa): ?>
          <div style="background:var(--card-bg);border:2px solid <?= $mesa['status']==='livre' ? 'rgba(92,184,92,0.4)' : ($mesa['status']==='ocupada' ? 'rgba(224,92,92,0.4)' : 'rgba(230,168,23,0.4)') ?>;border-radius:14px;padding:16px;text-align:center;min-width:110px;">
            <div style="font-size:2rem;">🪑</div>
            <div style="font-weight:700;color:#e8e8f0;font-size:1rem;">Mesa <?= $mesa['numero'] ?></div>
            <div style="font-size:0.72rem;color:#888;margin-bottom:8px;"><?= htmlspecialchars($mesa['localizacao']) ?> · <?= $mesa['capacidade'] ?> lugares</div>
            <?= getMesaStatusBadge($mesa['status']) ?>
            <div style="margin-top:10px;display:flex;gap:6px;justify-content:center;">
              <a href="mesas.php?editar=<?= $mesa['id'] ?>" class="btn btn-info btn-sm" title="Editar">✏️</a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir?')">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" value="<?= $mesa['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm" title="Excluir">🗑️</button>
              </form>
            </div>
            <div style="margin-top:6px;">
              <?php if ($mesa['status'] !== 'livre'): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="acao" value="status">
                <input type="hidden" name="id" value="<?= $mesa['id'] ?>">
                <input type="hidden" name="novo_status" value="livre">
                <button type="submit" class="btn btn-success btn-sm">✅ Liberar</button>
              </form>
              <?php else: ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="acao" value="status">
                <input type="hidden" name="id" value="<?= $mesa['id'] ?>">
                <input type="hidden" name="novo_status" value="ocupada">
                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:0.7rem;">Ocupar</button>
              </form>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require 'footer.php'; ?>