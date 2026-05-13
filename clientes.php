<?php
session_start();
require 'functions.php';
verificarSessao();
$page_title = 'Clientes';
$db = lerDB();
$msg = ''; $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'salvar') {
        $id = intval($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $cpf = trim($_POST['cpf'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        if (!$nome) { $erro = 'Nome é obrigatório.'; }
        else {
            $db = lerDB();
            if ($id > 0) {
                foreach ($db['clientes'] as &$c) {
                    if ($c['id'] == $id) { $c['nome']=$nome;$c['email']=$email;$c['telefone']=$telefone;$c['cpf']=$cpf;$c['endereco']=$endereco; break; }
                }
                $msg = 'Cliente atualizado!';
            } else {
                $novo_id = proximoId('clientes');
                $db = lerDB();
                $db['clientes'][] = ['id'=>$novo_id,'nome'=>$nome,'email'=>$email,'telefone'=>$telefone,'cpf'=>$cpf,'endereco'=>$endereco,'criado_em'=>dataAtual()];
                $msg = 'Cliente cadastrado!';
            }
            salvarDB($db);
            $db = lerDB();
        }
    } elseif ($acao === 'excluir') {
        $id = intval($_POST['id'] ?? 0);
        $db = lerDB();
        $db['clientes'] = array_values(array_filter($db['clientes'], fn($c) => $c['id'] != $id));
        salvarDB($db);
        $db = lerDB();
        $msg = 'Cliente excluído!';
    }
}

$edit = null;
if (isset($_GET['editar'])) $edit = buscarPorId('clientes', intval($_GET['editar']));
$busca = trim($_GET['busca'] ?? '');
$clientes_lista = $db['clientes'] ?? [];
if ($busca) {
    $clientes_lista = array_filter($clientes_lista, fn($c) => stripos($c['nome'], $busca) !== false || stripos($c['email'], $busca) !== false || stripos($c['telefone'], $busca) !== false);
}
require 'header.php';
?>
<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($erro) ?></div><?php endif; ?>
<div class="row" style="margin-bottom:24px;">
  <div class="col-6" style="max-width:400px;">
    <div class="card">
      <div class="card-header"><h3><?= $edit ? '✏️ Editar Cliente' : '➕ Novo Cliente' ?></h3></div>
      <div class="card-body">
        <form method="POST" action="clientes.php">
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="id" value="<?= $edit ? $edit['id'] : 0 ?>">
          <div class="form-group"><label>Nome Completo *</label><input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($edit['nome'] ?? '') ?>"></div>
          <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit['email'] ?? '') ?>"></div>
          <div class="form-group"><label>Telefone</label><input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($edit['telefone'] ?? '') ?>"></div>
          <div class="form-group"><label>CPF</label><input type="text" name="cpf" class="form-control" value="<?= htmlspecialchars($edit['cpf'] ?? '') ?>"></div>
          <div class="form-group"><label>Endereço</label><input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($edit['endereco'] ?? '') ?>"></div>
          <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><?= $edit ? '💾 Atualizar' : '➕ Cadastrar' ?></button>
            <?php if ($edit): ?><a href="clientes.php" class="btn btn-secondary">✖ Cancelar</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-6">
    <div class="card">
      <div class="card-header">
        <h3>👥 Clientes</h3>
        <form method="GET" action="clientes.php" style="display:flex;gap:8px;">
          <input type="text" name="busca" placeholder="Buscar..." class="form-control" style="max-width:180px;" value="<?= htmlspecialchars($busca) ?>">
          <button type="submit" class="btn btn-secondary btn-sm">🔍</button>
          <?php if ($busca): ?><a href="clientes.php" class="btn btn-secondary btn-sm">✖</a><?php endif; ?>
        </form>
      </div>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>Nome</th><th>Telefone</th><th>Email</th><th>Desde</th><th>Ações</th></tr></thead>
          <tbody>
            <?php if (empty($clientes_lista)): ?>
            <tr><td colspan="5" style="text-align:center;padding:24px;color:#888;">Nenhum cliente encontrado</td></tr>
            <?php else: ?>
            <?php foreach (array_reverse($clientes_lista) as $cli): ?>
            <tr>
              <td><strong><?= htmlspecialchars($cli['nome']) ?></strong><br><small style="color:#888;"><?= htmlspecialchars($cli['cpf']) ?></small></td>
              <td><?= htmlspecialchars($cli['telefone']) ?></td>
              <td style="color:#888;"><?= htmlspecialchars($cli['email']) ?></td>
              <td style="color:#888;"><?= date('d/m/Y', strtotime($cli['criado_em'])) ?></td>
              <td>
                <a href="clientes.php?editar=<?= $cli['id'] ?>" class="btn btn-info btn-sm">✏️</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir?')">
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="id" value="<?= $cli['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require 'footer.php'; ?>