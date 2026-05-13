<?php
session_start();

require 'functions.php';
//require 'config/database.php';

verificarSessao();

$page_title = 'Clientes';

$pdo = db();

$msg = '';
$erro = '';

// PROCESSAMENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    // SALVAR
    if ($acao === 'salvar') {

        $id = intval($_POST['id'] ?? 0);

        $nome = trim($_POST['nome'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $ativo = isset($_POST['ativo']) ? true : false;

        if (!$nome) {

            $erro = 'Nome do cliente é obrigatório.';

        } else {

            // VERIFICA E-MAIL DUPLICADO
            if (!empty($email)) {

                $sqlEmail = "
                    SELECT id
                    FROM clientes
                    WHERE email = :email
                ";

                if ($id > 0) {
                    $sqlEmail .= " AND id != :id";
                }

                $stmtEmail = $pdo->prepare($sqlEmail);

                $paramsEmail = [
                    ':email' => $email
                ];

                if ($id > 0) {
                    $paramsEmail[':id'] = $id;
                }

                $stmtEmail->execute($paramsEmail);

                $emailExiste = $stmtEmail->fetch();

                if ($emailExiste) {
                    $erro = 'Este e-mail já está cadastrado.';
                }
            }

            if (!$erro) {

                // UPDATE
                if ($id > 0) {

                    $sql = "
                        UPDATE clientes
                        SET
                            nome = :nome,
                            telefone = :telefone,
                            email = :email,
                            endereco = :endereco,
                            ativo = :ativo
                        WHERE id = :id
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        ':nome' => $nome,
                        ':telefone' => $telefone,
                        ':email' => $email ?: null,
                        ':endereco' => $endereco,
                        ':ativo' => $ativo,
                        ':id' => $id
                    ]);

                    $msg = 'Cliente atualizado com sucesso!';

                } else {

                    // INSERT
                    $sql = "
                        INSERT INTO clientes
                        (
                            nome,
                            telefone,
                            email,
                            endereco,
                            ativo
                        )
                        VALUES
                        (
                            :nome,
                            :telefone,
                            :email,
                            :endereco,
                            :ativo
                        )
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        ':nome' => $nome,
                        ':telefone' => $telefone,
                        ':email' => $email ?: null,
                        ':endereco' => $endereco,
                        ':ativo' => $ativo
                    ]);

                    $msg = 'Cliente cadastrado com sucesso!';
                }
            }
        }
    }

    // EXCLUIR
    if ($acao === 'excluir') {

        $id = intval($_POST['id'] ?? 0);

        // VERIFICA PEDIDOS
        $stmtPedidos = $pdo->prepare("
            SELECT id
            FROM pedidos
            WHERE cliente_id = :id
            LIMIT 1
        ");

        $stmtPedidos->execute([
            ':id' => $id
        ]);

        $pedidoExiste = $stmtPedidos->fetch();

        if ($pedidoExiste) {

            $erro = 'Não é possível excluir um cliente vinculado a pedidos.';

        } else {

            $stmt = $pdo->prepare("
                DELETE FROM clientes
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id
            ]);

            $msg = 'Cliente excluído com sucesso!';
        }
    }
}

// EDITAR
$edit = null;

if (isset($_GET['editar'])) {

    $id = intval($_GET['editar']);

    $stmt = $pdo->prepare("
        SELECT *
        FROM clientes
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $edit = $stmt->fetch();
}

// LISTAGEM
$sql = "
    SELECT
        c.*,
        (
            SELECT COUNT(*)
            FROM pedidos p
            WHERE p.cliente_id = c.id
        ) AS total_pedidos
    FROM clientes c
    ORDER BY c.id DESC
";

$stmt = $pdo->query($sql);

$clientes = $stmt->fetchAll();

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

    <!-- FORMULÁRIO -->
    <div class="col-4" style="max-width:420px;">

        <div class="card">

            <div class="card-header">
                <h3>
                    <?= $edit ? 'Editar Cliente' : 'Novo Cliente' ?>
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

                    <!-- TELEFONE -->
                    <div class="form-group">

                        <label>Telefone</label>

                        <input
                            type="text"
                            name="telefone"
                            class="form-control"
                            value="<?= htmlspecialchars($edit['telefone'] ?? '') ?>"
                        >
                    </div>

                    <!-- EMAIL -->
                    <div class="form-group">

                        <label>E-mail</label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($edit['email'] ?? '') ?>"
                        >
                    </div>

                    <!-- ENDEREÇO -->
                    <div class="form-group">

                        <label>Endereço</label>

                        <textarea
                            name="endereco"
                            class="form-control"
                        ><?= htmlspecialchars($edit['endereco'] ?? '') ?></textarea>
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
                                href="clientes.php"
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
    <div class="col-8">

        <div class="card">

            <div class="card-header">
                <h3>Clientes Cadastrados</h3>
            </div>

            <table>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Telefone</th>
                        <th>E-mail</th>
                        <th>Pedidos</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($clientes)): ?>

                        <tr>
                            <td
                                colspan="7"
                                style="text-align:center;padding:24px;color:#888;"
                            >
                                Nenhum cliente encontrado
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($clientes as $cliente): ?>

                            <tr>

                                <td>
                                    <?= $cliente['id'] ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($cliente['nome']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente['telefone']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($cliente['email']) ?>
                                </td>

                                <td>
                                    <span class="badge badge-info">
                                        <?= $cliente['total_pedidos'] ?>
                                    </span>
                                </td>

                                <td>

                                    <?= $cliente['ativo']
                                        ? '<span class="badge badge-success">Ativo</span>'
                                        : '<span class="badge badge-danger">Inativo</span>' ?>

                                </td>

                                <td>

                                    <!-- EDITAR -->
                                    <a
                                        href="clientes.php?editar=<?= $cliente['id'] ?>"
                                        class="btn btn-info btn-sm"
                                    >
                                        Editar
                                    </a>

                                    <!-- EXCLUIR -->
                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Deseja excluir este cliente?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="excluir"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $cliente['id'] ?>"
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