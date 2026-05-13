<?php
session_start();
require 'functions.php';
verificarSessao();

$page_title = 'Financeiro';
$pdo = db();

$msg = '';
$erro = '';

// =====================================
// PROCESSAMENTO (mantido igual)
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $id = intval($_POST['id'] ?? 0);
        $pedido_id = intval($_POST['pedido_id'] ?? 0);
        $tipo = trim($_POST['tipo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = floatval($_POST['valor'] ?? 0);

        if (!$tipo) {
            $erro = 'Tipo da movimentação é obrigatório.';
        } elseif ($valor <= 0) {
            $erro = 'Informe um valor válido.';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE financeiro SET pedido_id=:pedido_id, tipo=:tipo, descricao=:descricao, valor=:valor WHERE id=:id");
                $stmt->execute([':pedido_id' => $pedido_id ?: null, ':tipo' => $tipo, ':descricao' => $descricao, ':valor' => $valor, ':id' => $id]);
                $msg = 'Movimentação atualizada com sucesso!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO financeiro (pedido_id, tipo, descricao, valor) VALUES (:pedido_id, :tipo, :descricao, :valor)");
                $stmt->execute([':pedido_id' => $pedido_id ?: null, ':tipo' => $tipo, ':descricao' => $descricao, ':valor' => $valor]);
                $msg = 'Movimentação cadastrada com sucesso!';
            }
        }
    }

    if ($acao === 'excluir') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM financeiro WHERE id = :id")->execute([':id' => $id]);
        $msg = 'Movimentação excluída com sucesso!';
    }
}

// EDITAR
$edit = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM financeiro WHERE id = :id");
    $stmt->execute([':id' => intval($_GET['editar'])]);
    $edit = $stmt->fetch();
}

// PEDIDOS
$pedidos = $pdo->query("SELECT id, total FROM pedidos ORDER BY id DESC")->fetchAll();

// MOVIMENTAÇÕES
$movimentacoes = $pdo->query("
    SELECT f.*, p.total AS pedido_total 
    FROM financeiro f 
    LEFT JOIN pedidos p ON p.id = f.pedido_id 
    ORDER BY f.id DESC
")->fetchAll();

// RESUMO
$resumo = $pdo->query("
    SELECT 
        COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) AS total_entradas,
        COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) AS total_saidas
    FROM financeiro
")->fetch();

$totalEntradas = floatval($resumo['total_entradas']);
$totalSaidas   = floatval($resumo['total_saidas']);
$saldo         = $totalEntradas - $totalSaidas;

require 'header.php';
?>

<style>
  .finance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.summary-card {
    background: #151522;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.summary-card h5 {
    margin: 0;
    font-size: 14px;
    color: #aaa;
}

.summary-card h2 {
    margin: 5px 0 0;
    font-size: 24px;
}

.chart-container {
    background: #151522;
    border-radius: 12px;
    padding: 20px;
    height: 100%;
}

.resumo-box {
    background: #151522;
    border-radius: 12px;
    padding: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 14px 10px;
    text-align: left;
}

th {
    background: #151522;
    font-weight: 600;
}  
</style>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

    <!-- CARDS SUPERIORES -->
<div class="finance-grid">
    <div class="summary-card">
        <div style="font-size:32px;">💰</div>
        <div>
            <h5>RECEITA TOTAL</h5>
            <h2 style="color:#4ade80;">R$ <?= number_format($totalEntradas, 2, ',', '.') ?></h2>
        </div>
    </div>

    <div class="summary-card">
        <div style="font-size:32px;">💵</div>
        <div>
            <h5>DINHEIRO</h5>
            <h2>R$ 0,00</h2>
        </div>
    </div>

    <div class="summary-card">
        <div style="font-size:32px;">📱</div>
        <div>
            <h5>PIX</h5>
            <h2>R$ 0,00</h2>
        </div>
    </div>

    <div class="summary-card">
        <div style="font-size:32px;">💳</div>
        <div>
            <h5>CARTÃO</h5>
            <h2 style="color:#4ade80;">R$ <?= number_format($totalEntradas, 2, ',', '.') ?></h2>
        </div>
    </div>
</div>

<div class="row">

    <!-- FORMULÁRIO -->
    <div class="col-4" style="max-width:420px;">

        <div class="card">

            <div class="card-header">
                <h3>
                    <?= $edit ? 'Editar Movimentação' : 'Nova Movimentação' ?>
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

                    <!-- PEDIDO -->
                    <div class="form-group">

                        <label>Pedido</label>

                        <select
                            name="pedido_id"
                            class="form-control"
                        >

                            <option value="">
                                Sem pedido
                            </option>

                            <?php foreach ($pedidos as $pedido): ?>

                                <option
                                    value="<?= $pedido['id'] ?>"
                                    <?= (($edit['pedido_id'] ?? 0) == $pedido['id']) ? 'selected' : '' ?>
                                >
                                    Pedido #<?= $pedido['id'] ?>
                                    - R$ <?= number_format($pedido['total'], 2, ',', '.') ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <!-- TIPO -->
                    <div class="form-group">

                        <label>Tipo *</label>

                        <select
                            name="tipo"
                            class="form-control"
                            required
                        >

                            <?php
                            $tipoAtual = $edit['tipo'] ?? '';
                            ?>

                            <option value="">
                                Selecione
                            </option>

                            <option
                                value="entrada"
                                <?= $tipoAtual === 'entrada' ? 'selected' : '' ?>
                            >
                                Entrada
                            </option>

                            <option
                                value="saida"
                                <?= $tipoAtual === 'saida' ? 'selected' : '' ?>
                            >
                                Saída
                            </option>

                        </select>
                    </div>

                    <!-- DESCRIÇÃO -->
                    <div class="form-group">

                        <label>Descrição</label>

                        <textarea
                            name="descricao"
                            class="form-control"
                        ><?= htmlspecialchars($edit['descricao'] ?? '') ?></textarea>
                    </div>

                    <!-- VALOR -->
                    <div class="form-group">

                        <label>Valor *</label>

                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="valor"
                            class="form-control"
                            required
                            value="<?= $edit['valor'] ?? '' ?>"
                        >
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
                                href="financeiro.php"
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
                <h3>Movimentações Financeiras</h3>
            </div>

            <table>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pedido</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($movimentacoes)): ?>

                        <tr>
                            <td
                                colspan="7"
                                style="text-align:center;padding:24px;color:#888;"
                            >
                                Nenhuma movimentação encontrada
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($movimentacoes as $mov): ?>

                            <tr>

                                <td>
                                    <?= $mov['id'] ?>
                                </td>

                                <td>

                                    <?php if ($mov['pedido_id']): ?>

                                        <span class="badge badge-info">
                                            Pedido #<?= $mov['pedido_id'] ?>
                                        </span>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ($mov['tipo'] === 'entrada'): ?>

                                        <span class="badge badge-success">
                                            Entrada
                                        </span>

                                    <?php else: ?>

                                        <span class="badge badge-danger">
                                            Saída
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= htmlspecialchars($mov['descricao']) ?>
                                </td>

                                <td>

                                    <strong
                                        style="<?= $mov['tipo'] === 'entrada'
                                            ? 'color:green;'
                                            : 'color:red;' ?>"
                                    >
                                        R$ <?= number_format($mov['valor'], 2, ',', '.') ?>
                                    </strong>

                                </td>

                                <td>
                                    <?= date('d/m/Y H:i', strtotime($mov['criado_em'])) ?>
                                </td>

                                <td>

                                    <!-- EDITAR -->
                                    <a
                                        href="financeiro.php?editar=<?= $mov['id'] ?>"
                                        class="btn btn-info btn-sm"
                                    >
                                        Editar
                                    </a>

                                    <!-- EXCLUIR -->
                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Deseja excluir esta movimentação?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="excluir"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $mov['id'] ?>"
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