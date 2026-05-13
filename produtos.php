<?php
session_start();
require 'functions.php';
verificarSessao();
$page_title = 'Produtos';
$db = lerDB();
$msg = ''; $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'salvar') {
        $id = intval($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = floatval(str_replace(',','.', $_POST['preco'] ?? 0));
        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $estoque = intval($_POST['estoque'] ?? 0);
        $ativo = isset($_POST['ativo']) ? true : false;
        if (!$nome) { $erro = 'Nome é obrigatório.'; }
        else {
            $db = lerDB();
            if ($id > 0) {
                foreach ($db['produtos'] as &$p) {
                    if ($p['id'] == $id) {
                        $p['nome']=$nome; $p['descricao']=$descricao; $p['preco']=$preco;
                        $p['categoria_id']=$categoria_id; $p['estoque']=$estoque; $p['ativo']=$ativo;
                        break;
                    }
                }
                $msg = 'Produto atualizado com sucesso!';
            } else {
                $novo_id = proximoId('produtos');
                $db = lerDB();
                $db['produtos'][] = ['id'=>$novo_id,'nome'=>$nome,'descricao'=>$descricao,'preco'=>$preco,'categoria_id'=>$categoria_id,'estoque'=>$estoque,'ativo'=>$ativo];
                $msg = 'Produto cadastrado com sucesso!';
            }
            salvarDB($db);
            $db = lerDB();
        }
    } elseif ($acao === 'excluir') {
        $id = intval($_POST['id'] ?? 0);
        $db = lerDB();
        $db['produtos'] = array_values(array_filter($db['produtos'], fn($p) => $p['id'] != $id));
        salvarDB($db);
        $db = lerDB();
        $msg = 'Produto excluído!';
    }
}

$edit_produto = null;
if (isset($_GET['editar'])) {
    $edit_produto = buscarPorId('produtos', intval($_GET['editar']));
}
require 'header.php';
?>
<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($erro) ?></div><?php endif; ?>
<div class="row" style="margin-bottom:24px;">
  <div class="col-6">
    <div class="card">
      <div class="card-header"><h3><?= $edit_produto ? '✏️ Editar Produto' : '➕ Novo Produto' ?></h3></div>
      <div class="card-body">
        <form method="POST" action="produtos.php">
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="id" value="<?= $edit_produto ? $edit_produto['id'] : 0 ?>">
          <div class="form-group">
            <label>Nome do Produto *</label>
            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($edit_produto['nome'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Descrição</label>
            <textarea name="descricao" class="form-control"><?= htmlspecialchars($edit_produto['descricao'] ?? '') ?></textarea>
          </div>
          <div class="row">
            <div class="col-6">
              <div class="form-group">
                <label>Preço (R$) *</label>
                <input type="number" name="preco" step="0.01" min="0" class="form-control" required value="<?= $edit_produto ? number_format($edit_produto['preco'], 2, '.', '') : '' ?>">
              </div>
            </div>
            <div class="col-6">
              <div class="form-group">
                <label>Estoque</label>
                <input type="number" name="estoque" min="0" class="form-control" value="<?= $edit_produto['estoque'] ?? 0 ?>">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Categoria</label>
            <select name="categoria_id" class="form-control">
              <option value="0">Sem categoria</option>
              <?php foreach (($db['categorias'] ?? []) as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($edit_produto['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label style="flex-direction:row;display:flex;align-items:center;gap:8px;">
              <input type="checkbox" name="ativo" <?= ($edit_produto['ativo'] ?? true) ? 'checked' : '' ?> style="width:auto;"> Produto Ativo
            </label>
          </div>
          <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><?= $edit_produto ? '💾 Atualizar' : '➕ Cadastrar' ?></button>
            <?php if ($edit_produto): ?>
            <a href="produtos.php" class="btn btn-secondary">✖ Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-6">
    <div class="card">
      <div class="card-header"><h3>📋 Lista de Produtos</h3></div>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>Nome</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php if (empty($db['produtos'])): ?>
            <tr><td colspan="6" style="text-align:center;padding:24px;color:#888;">Nenhum produto cadastrado</td></tr>
            <?php else: ?>
            <?php foreach (array_reverse($db['produtos']) as $prod): ?>
            <tr>
              <td><strong><?= htmlspecialchars($prod['nome']) ?></strong><br><small style="color:#888;"><?= htmlspecialchars(substr($prod['descricao'],0,40)) ?></small></td>
              <td><?= htmlspecialchars(getNomeCategoria($prod['categoria_id'])) ?></td>
              <td style="color:#e8a87c;font-weight:700;"><?= formatarMoeda($prod['preco']) ?></td>
              <td><span class="badge <?= $prod['estoque'] > 10 ? 'badge-success' : ($prod['estoque'] > 0 ? 'badge-warning' : 'badge-danger') ?>"><?= $prod['estoque'] ?></span></td>
              <td><?= $prod['ativo'] ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge badge-danger">Inativo</span>' ?></td>
              <td>
                <a href="produtos.php?editar=<?= $prod['id'] ?>" class="btn btn-info btn-sm">✏️</a>
                <form method="POST" action="produtos.php" style="display:inline;" onsubmit="return confirm('Excluir produto?')">
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="id" value="<?= $prod['id'] ?>">
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