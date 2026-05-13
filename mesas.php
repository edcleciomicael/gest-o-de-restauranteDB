<?php
session_start();
require 'functions.php';
verificarSessao();

$page_title = 'Mesas';
$pdo = db();

$msg = '';
$erro = '';

// PROCESSAMENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $id = intval($_POST['id'] ?? 0);
        $numero = trim($_POST['numero'] ?? '');
        $capacidade = intval($_POST['capacidade'] ?? 4);
        $localizacao = trim($_POST['localizacao'] ?? '');
        $status = trim($_POST['status'] ?? 'livre');

        if (empty($numero)) {
            $erro = 'Número da mesa é obrigatório.';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE mesas SET numero=:numero, capacidade=:capacidade, localizacao=:localizacao, status=:status WHERE id=:id");
                $stmt->execute([':numero'=>$numero, ':capacidade'=>$capacidade, ':localizacao'=>$localizacao, ':status'=>$status, ':id'=>$id]);
                $msg = 'Mesa atualizada com sucesso!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO mesas (numero, capacidade, localizacao, status) VALUES (:numero, :capacidade, :localizacao, :status)");
                $stmt->execute([':numero'=>$numero, ':capacidade'=>$capacidade, ':localizacao'=>$localizacao, ':status'=>$status]);
                $msg = 'Mesa cadastrada com sucesso!';
            }
        }
    }

    // Outras ações
    if (in_array($acao, ['ocupar','liberar','reservar','excluir'])) {
        $id = intval($_POST['id'] ?? 0);
        if ($acao === 'ocupar') $pdo->prepare("UPDATE mesas SET status='ocupada' WHERE id=:id")->execute([':id'=>$id]);
        if ($acao === 'liberar') $pdo->prepare("UPDATE mesas SET status='livre' WHERE id=:id")->execute([':id'=>$id]);
        if ($acao === 'reservar') $pdo->prepare("UPDATE mesas SET status='reservada' WHERE id=:id")->execute([':id'=>$id]);
        if ($acao === 'excluir') $pdo->prepare("DELETE FROM mesas WHERE id=:id")->execute([':id'=>$id]);
        
        $msg = 'Operação realizada com sucesso!';
    }
}

// EDITAR
$edit = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM mesas WHERE id = :id");
    $stmt->execute([':id' => intval($_GET['editar'])]);
    $edit = $stmt->fetch();
}

// LISTAGEM
$mesas = $pdo->query("SELECT * FROM mesas ORDER BY numero ASC")->fetchAll();

require 'header.php';
?>

<style>
/* === AJUSTE PRINCIPAL PARA FICAR LADO A LADO === */
.row {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    padding: 20px;
}

.col-3 {
    flex: 0 0 360px;           /* Largura do formulário */
    max-width: 360px;
}

.col-9 {
    flex: 1;
}

/* Grid das mesas */
.mesas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); /* Aumentei o tamanho */
    gap: 20px;
}

.mesa-card {
    background: #151522;
    border-radius: 16px;
    padding: 22px 18px;
    border: 2px solid transparent;
    transition: 0.2s;
    color: #fff;
    min-height: 260px;           /* Altura mínima */
}

.mesa-card:hover {
    transform: translateY(-3px);
}

.mesa-topo {
    text-align: center;
    margin-bottom: 12px;
}

.mesa-topo h3 {
    margin: 8px 0;
    font-size: 18px;
}

.mesa-topo p {
    color: #aaa;
    font-size: 13px;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
}

.status-livre { background: rgba(40,167,69,.2); color: #4ade80; }
.status-ocupada { background: rgba(220,53,69,.2); color: #ff6b6b; }
.status-reservada { background: rgba(255,193,7,.2); color: #ffd166; }

/*.mesa-actions {
    margin-top: 12px;
    display: grid;
    gap: 6px;
}*/
.mesa-actions {
    margin-top: 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;     /* Centraliza os botões */
}

.mesa-actions .btn {
    min-width: 115px;        /* Ajuste este valor se quiser mais largo ou mais estreito */
    text-align: center;
}

</style>

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
    <div class="col-3">
        <div class="card">
            <div class="card-header">
                <h3>
                    <?= $edit ? 'Editar Mesa' : 'Nova Mesa' ?>
                </h3>
            </div>
            <div class="card-body">

                <form method="POST">
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

                    <div class="form-group">
                        <label>Número *</label>
                        <input type="text" name="numero" class="form-control" required 
                               value="<?= htmlspecialchars($edit['numero'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Capacidade</label>
                        <input type="number" name="capacidade" class="form-control" 
                               value="<?= $edit['capacidade'] ?? 4 ?>">
                    </div>

                    <div class="form-group">
                        <label>Localização</label>
                        <input type="text" name="localizacao" class="form-control" 
                               value="<?= htmlspecialchars($edit['localizacao'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <?= $edit ? 'Salvar Alterações' : 'Cadastrar' ?>
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- MAPA DE MESAS (lado direito) -->
    <div class="col-9">
        <div class="mesas-grid">
            <?php foreach ($mesas as $mesa): ?>
            <div class="mesa-card <?= $mesa['status'] ?>">

                <div class="mesa-topo">
                    <div style="font-size: 42px;">🪑</div>
                    <h3>Mesa <?= htmlspecialchars($mesa['numero']) ?></h3>
                    <p>
                        <?= htmlspecialchars($mesa['localizacao']) ?> • 
                        <?= $mesa['capacidade'] ?> lugares
                    </p>
                    <span class="status-badge status-<?= $mesa['status'] ?>">
                        <?= strtoupper($mesa['status']) ?>
                    </span>
                </div>

                <div class="mesa-actions">
                    <a href="?editar=<?= $mesa['id'] ?>" class="btn btn-info btn-sm">✏️ Editar</a>

                    <?php if ($mesa['status'] !== 'ocupada'): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="acao" value="ocupar">
                        <input type="hidden" name="id" value="<?= $mesa['id'] ?>">
                        <button class="btn btn-danger btn-sm">Ocupar</button>
                    </form>
                    <?php endif; ?>

                    <?php if ($mesa['status'] !== 'livre'): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="acao" value="liberar">
                        <input type="hidden" name="id" value="<?= $mesa['id'] ?>">
                        <button class="btn btn-success btn-sm">Liberar</button>
                    </form>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
<?php require 'footer.php'; ?>