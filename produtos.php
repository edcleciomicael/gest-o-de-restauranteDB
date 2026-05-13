<?php
session_start();

require 'functions.php';
//require 'config/database.php';

verificarSessao();

$page_title = 'Produtos';

$pdo = db();

$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    // SALVAR
    if ($acao === 'salvar') {

        $id = intval($_POST['id'] ?? 0);

        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = floatval($_POST['preco'] ?? 0);
        $estoque = intval($_POST['estoque'] ?? 0);
        $imagem = trim($_POST['imagem'] ?? '');
        $ativo = isset($_POST['ativo']) ? true : false;

        if (!$nome) {

            $erro = 'Nome do produto é obrigatório.';

        } else {

            // UPDATE
            if ($id > 0) {

                $sql = "
                    UPDATE produtos
                    SET
                        categoria_id = :categoria_id,
                        nome = :nome,
                        descricao = :descricao,
                        preco = :preco,
                        estoque = :estoque,
                        imagem = :imagem,
                        ativo = :ativo
                    WHERE id = :id
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':categoria_id' => $categoria_id ?: null,
                    ':nome' => $nome,
                    ':descricao' => $descricao,
                    ':preco' => $preco,
                    ':estoque' => $estoque,
                    ':imagem' => $imagem,
                    ':ativo' => $ativo,
                    ':id' => $id
                ]);

                $msg = 'Produto atualizado com sucesso!';

            } else {

                // INSERT
                $sql = "
                    INSERT INTO produtos
                    (
                        categoria_id,
                        nome,
                        descricao,
                        preco,
                        estoque,
                        imagem,
                        ativo
                    )
                    VALUES
                    (
                        :categoria_id,
                        :nome,
                        :descricao,
                        :preco,
                        :estoque,
                        :imagem,
                        :ativo
                    )
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':categoria_id' => $categoria_id ?: null,
                    ':nome' => $nome,
                    ':descricao' => $descricao,
                    ':preco' => $preco,
                    ':estoque' => $estoque,
                    ':imagem' => $imagem,
                    ':ativo' => $ativo
                ]);

                $msg = 'Produto cadastrado com sucesso!';
            }
        }
    }

    // EXCLUIR
    if ($acao === 'excluir') {

        $id = intval($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("
            DELETE FROM produtos
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $msg = 'Produto excluído com sucesso!';
    }
}

// EDITAR
$edit = null;

if (isset($_GET['editar'])) {

    $id = intval($_GET['editar']);

    $stmt = $pdo->prepare("
        SELECT *
        FROM produtos
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $edit = $stmt->fetch();
}

// CATEGORIAS
$stmtCategorias = $pdo->query("
    SELECT *
    FROM categorias
    ORDER BY nome ASC
");

$categorias = $stmtCategorias->fetchAll();

// PRODUTOS
$sql = "
    SELECT
        p.*,
        c.nome AS categoria_nome
    FROM produtos p
    LEFT JOIN categorias c
        ON c.id = p.categoria_id
    ORDER BY p.id DESC
";

$stmt = $pdo->query($sql);

$produtos = $stmt->fetchAll();

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

    <!-- FORM -->
    <div class="col-6" style="max-width:420px;">

        <div class="card">

            <div class="card-header">
                <h3>
                    <?= $edit ? 'Editar Produto' : 'Novo Produto' ?>
                </h3>
            </div>

            <div class="card-body">

                <form method="POST">

                    <input type="hidden" name="acao" value="salvar">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $edit['id'] ?? 0 ?>"
                    >

                    <!-- CATEGORIA -->
                    <div class="form-group">

                        <label>Categoria</label>

                        <select
                            name="categoria_id"
                            class="form-control"
                        >

                            <option value="">
                                Selecione
                            </option>

                            <?php foreach ($categorias as $categoria): ?>

                                <option
                                    value="<?= $categoria['id'] ?>"
                                    <?= (($edit['categoria_id'] ?? 0) == $categoria['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($categoria['nome']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <!-- NOME -->
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

                    <!-- DESCRIÇÃO -->
                    <div class="form-group">

                        <label>Descrição</label>

                        <textarea
                            name="descricao"
                            class="form-control"
                        ><?= htmlspecialchars($edit['descricao'] ?? '') ?></textarea>
                    </div>

                    <!-- PREÇO -->
                    <div class="form-group">

                        <label>Preço</label>

                        <input
                            type="number"
                            step="0.01"
                            name="preco"
                            class="form-control"
                            value="<?= $edit['preco'] ?? 0 ?>"
                        >
                    </div>

                    <!-- ESTOQUE -->
                    <div class="form-group">

                        <label>Estoque</label>

                        <input
                            type="number"
                            name="estoque"
                            class="form-control"
                            value="<?= $edit['estoque'] ?? 0 ?>"
                        >
                    </div>

                    <!-- IMAGEM -->
                    <div class="form-group">

                        <label>Imagem URL</label>

                        <input
                            type="text"
                            name="imagem"
                            class="form-control"
                            value="<?= htmlspecialchars($edit['imagem'] ?? '') ?>"
                        >
                    </div>

                    <!-- ATIVO -->
                    <div class="form-group">

                        <label style="display:flex;align-items:center;gap:8px;">

                            <input
                                type="checkbox"
                                name="ativo"
                                style="width:auto;"
                                <?= ($edit['ativo'] ?? true) ? 'checked' : '' ?>
                            >

                            Ativo
                        </label>
                    </div>

                    <!-- BOTÕES -->
                    <div style="display:flex;gap:10px;">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <?= $edit ? 'Atualizar' : 'Cadastrar' ?>
                        </button>

                        <?php if ($edit): ?>

                            <a
                                href="produtos.php"
                                class="btn btn-secondary"
                            >
                                Cancelar
                            </a>

                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- LISTAGEM -->
    <div class="col-6">

        <div class="card">

            <div class="card-header">
                <h3>Produtos Cadastrados</h3>
            </div>

            <table>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Estoque</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($produtos)): ?>

                        <tr>
                            <td
                                colspan="7"
                                style="text-align:center;padding:24px;color:#888;"
                            >
                                Nenhum produto encontrado
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($produtos as $produto): ?>

                            <tr>

                                <td>
                                    <?= $produto['id'] ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($produto['nome']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($produto['categoria_nome'] ?? 'Sem categoria') ?>
                                </td>

                                <td>
                                    R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <?= $produto['estoque'] ?>
                                </td>

                                <td>
                                    <?= $produto['ativo']
                                        ? '<span class="badge badge-success">Ativo</span>'
                                        : '<span class="badge badge-danger">Inativo</span>' ?>
                                </td>

                                <td>

                                    <!-- EDITAR -->
                                    <a
                                        href="produtos.php?editar=<?= $produto['id'] ?>"
                                        class="btn btn-info btn-sm"
                                    >
                                        Editar
                                    </a>

                                    <!-- EXCLUIR -->
                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Deseja excluir este produto?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="excluir"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $produto['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
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