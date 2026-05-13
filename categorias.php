<?php
session_start();

require 'functions.php';
//require 'config/database.php';

verificarSessao();

$page_title = 'Categorias';

$msg = '';
$erro = '';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {

        $id = intval($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $ativo = isset($_POST['ativo']) ? true : false;

        if (!$nome) {
            $erro = 'Nome é obrigatório.';
        } else {

            if ($id > 0) {

                $sql = "
                    UPDATE categorias
                    SET
                        nome = :nome,
                        descricao = :descricao,
                        ativo = :ativo
                    WHERE id = :id
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':nome' => $nome,
                    ':descricao' => $descricao,
                    ':ativo' => $ativo,
                    ':id' => $id
                ]);

                $msg = 'Categoria atualizada com sucesso!';

            } else {

                $sql = "
                    INSERT INTO categorias
                    (nome, descricao, ativo)
                    VALUES
                    (:nome, :descricao, :ativo)
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':nome' => $nome,
                    ':descricao' => $descricao,
                    ':ativo' => $ativo
                ]);

                $msg = 'Categoria cadastrada com sucesso!';
            }
        }
    }

    if ($acao === 'excluir') {

        $id = intval($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = :id");

        $stmt->execute([
            ':id' => $id
        ]);

        $msg = 'Categoria excluída com sucesso!';
    }
}

$edit = null;

if (isset($_GET['editar'])) {

    $id = intval($_GET['editar']);

    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");

    $stmt->execute([
        ':id' => $id
    ]);

    $edit = $stmt->fetch();
}

$sql = "
    SELECT
        c.*,
        COUNT(p.id) AS total_produtos
    FROM categorias c
    LEFT JOIN produtos p ON p.categoria_id = c.id
    GROUP BY c.id
    ORDER BY c.id DESC
";

$stmt = $pdo->query($sql);

$categorias = $stmt->fetchAll();

require 'header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success">
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<?php if ($erro): ?>
<div class="alert alert-danger">
    <?= htmlspecialchars($erro) ?>
</div>
<?php endif; ?>

<div class="row">

    <div class="col-6" style="max-width:380px;">

        <div class="card">

            <div class="card-header">
                <h3>
                    <?= $edit ? 'Editar Categoria' : 'Nova Categoria' ?>
                </h3>
            </div>

            <div class="card-body">

                <form method="POST" action="categorias.php">

                    <input type="hidden" name="acao" value="salvar">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $edit['id'] ?? 0 ?>"
                    >

                    <div class="form-group">
                        <label>Nome *</label>

                        <input
                            type="text"
                            name="nome"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($edit['nome'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Descrição</label>

                        <textarea
                            name="descricao"
                            class="form-control"
                        ><?= htmlspecialchars($edit['descricao'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;">

                            <input
                                type="checkbox"
                                name="ativo"
                                <?= ($edit['ativo'] ?? true) ? 'checked' : '' ?>
                                style="width:auto;"
                            >

                            Ativo
                        </label>
                    </div>

                    <div style="display:flex;gap:10px;">

                        <button type="submit" class="btn btn-primary">
                            <?= $edit ? 'Atualizar' : 'Cadastrar' ?>
                        </button>

                        <?php if ($edit): ?>
                            <a href="categorias.php" class="btn btn-secondary">
                                Cancelar
                            </a>
                        <?php endif; ?>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-6">

        <div class="card">

            <div class="card-header">
                <h3>Categorias Cadastradas</h3>
            </div>

            <table>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Produtos</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($categorias)): ?>

                        <tr>
                            <td colspan="6" style="text-align:center;padding:24px;color:#888;">
                                Nenhuma categoria encontrada
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($categorias as $cat): ?>

                            <tr>

                                <td><?= $cat['id'] ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($cat['nome']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cat['descricao']) ?>
                                </td>

                                <td>
                                    <span class="badge badge-info">
                                        <?= $cat['total_produtos'] ?>
                                    </span>
                                </td>

                                <td>
                                    <?= $cat['ativo']
                                        ? '<span class="badge badge-success">Ativo</span>'
                                        : '<span class="badge badge-danger">Inativo</span>' ?>
                                </td>

                                <td>

                                    <a
                                        href="categorias.php?editar=<?= $cat['id'] ?>"
                                        class="btn btn-info btn-sm"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Deseja excluir?')"
                                    >

                                        <input type="hidden" name="acao" value="excluir">

                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">

                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Excluir
                                        </button>
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

<?php require 'footer.php'; ?>