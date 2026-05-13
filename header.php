<?php
if (!isset($page_title)) $page_title = 'Dashboard';
$current_page = basename($_SERVER['PHP_SELF']);
$menu = [
    ['file' => 'index.php',      'icon' => '📊', 'label' => 'Dashboard'],
    ['file' => 'pedidos.php',    'icon' => '🧾', 'label' => 'Pedidos'],
    ['file' => 'mesas.php',      'icon' => '🪑', 'label' => 'Mesas'],
    ['file' => 'produtos.php',   'icon' => '🍽️', 'label' => 'Produtos'],
    ['file' => 'categorias.php', 'icon' => '📂', 'label' => 'Categorias'],
    ['file' => 'clientes.php',   'icon' => '👥', 'label' => 'Clientes'],
    ['file' => 'financeiro.php', 'icon' => '💰', 'label' => 'Financeiro'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — Restaurante Manager</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#0f0f13;--sidebar-bg:#141418;--sidebar-border:rgba(255,255,255,0.06);--card-bg:#1a1a22;--card-border:rgba(255,255,255,0.08);--text:#e8e8f0;--text-muted:#888;--accent:#e8a87c;--accent2:#c97b4b;--danger:#e05c5c;--success:#5cb85c;--info:#5b9bd5;--warning:#e6a817;}
body{min-height:100vh;background:var(--bg);color:var(--text);font-family:'Segoe UI',sans-serif;display:flex;}
.sidebar{width:240px;min-height:100vh;background:var(--sidebar-bg);border-right:1px solid var(--sidebar-border);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100;transition:transform .3s;}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid var(--sidebar-border);}
.sidebar-logo .logo-inner{display:flex;align-items:center;gap:12px;}
.sidebar-logo .ico{width:42px;height:42px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;}
.sidebar-logo h2{color:#fff;font-size:1rem;font-weight:700;}
.sidebar-logo span{color:var(--text-muted);font-size:0.72rem;}
.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto;}
.nav-item{display:flex;align-items:center;gap:12px;padding:12px 20px;color:var(--text-muted);text-decoration:none;font-size:0.88rem;font-weight:500;transition:all .2s;cursor:pointer;border-left:3px solid transparent;}
.nav-item:hover{color:var(--text);background:rgba(255,255,255,0.04);}
.nav-item.active{color:var(--accent);background:rgba(232,168,124,0.08);border-left-color:var(--accent);}
.nav-item .nav-icon{font-size:1.1rem;width:22px;text-align:center;}
.nav-section{padding:10px 20px 6px;font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;font-weight:700;}
.sidebar-footer{padding:16px 20px;border-top:1px solid var(--sidebar-border);}
.user-info{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.user-avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;}
.user-name{font-size:0.82rem;color:var(--text);font-weight:600;}
.user-role{font-size:0.70rem;color:var(--text-muted);}
.btn-logout{width:100%;padding:8px;background:rgba(224,92,92,0.12);border:1px solid rgba(224,92,92,0.2);border-radius:8px;color:#e05c5c;font-size:0.80rem;cursor:pointer;text-align:center;text-decoration:none;display:block;transition:all .2s;}
.btn-logout:hover{background:rgba(224,92,92,0.2);}
.main{margin-left:240px;flex:1;min-height:100vh;display:flex;flex-direction:column;}
.topbar{background:var(--card-bg);border-bottom:1px solid var(--card-border);padding:16px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;}
.topbar h1{font-size:1.15rem;font-weight:700;color:var(--text);}
.topbar-right{display:flex;align-items:center;gap:14px;}
.topbar-badge{background:rgba(232,168,124,0.1);border:1px solid rgba(232,168,124,0.2);color:var(--accent);padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;}
.content{padding:28px;flex:1;}
.card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;overflow:hidden;}
.card-header{padding:18px 22px;border-bottom:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between;}
.card-header h3{font-size:0.95rem;font-weight:700;color:var(--text);}
.card-body{padding:22px;}
.btn{padding:9px 18px;border-radius:9px;border:none;cursor:pointer;font-size:0.83rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s;}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(232,168,124,0.3);}
.btn-danger{background:rgba(224,92,92,0.15);border:1px solid rgba(224,92,92,0.25);color:var(--danger);}
.btn-danger:hover{background:rgba(224,92,92,0.25);}
.btn-info{background:rgba(91,155,213,0.15);border:1px solid rgba(91,155,213,0.25);color:var(--info);}
.btn-info:hover{background:rgba(91,155,213,0.25);}
.btn-success{background:rgba(92,184,92,0.15);border:1px solid rgba(92,184,92,0.25);color:var(--success);}
.btn-success:hover{background:rgba(92,184,92,0.25);}
.btn-secondary{background:rgba(255,255,255,0.06);border:1px solid var(--card-border);color:var(--text-muted);}
.btn-secondary:hover{background:rgba(255,255,255,0.10);color:var(--text);}
.btn-sm{padding:6px 12px;font-size:0.75rem;}
table{width:100%;border-collapse:collapse;}
table thead th{padding:10px 14px;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);border-bottom:1px solid var(--card-border);text-align:left;}
table tbody tr{border-bottom:1px solid rgba(255,255,255,0.04);transition:background .15s;}
table tbody tr:hover{background:rgba(255,255,255,0.02);}
table tbody td{padding:12px 14px;font-size:0.86rem;color:var(--text);}
.badge{padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;display:inline-block;}
.badge-success{background:rgba(92,184,92,0.15);color:#5cb85c;border:1px solid rgba(92,184,92,0.25);}
.badge-danger{background:rgba(224,92,92,0.15);color:#e05c5c;border:1px solid rgba(224,92,92,0.25);}
.badge-warning{background:rgba(230,168,23,0.15);color:#e6a817;border:1px solid rgba(230,168,23,0.25);}
.badge-info{background:rgba(91,155,213,0.15);color:#5b9bd5;border:1px solid rgba(91,155,213,0.25);}
.badge-primary{background:rgba(232,168,124,0.15);color:var(--accent);border:1px solid rgba(232,168,124,0.25);}
.badge-secondary{background:rgba(255,255,255,0.08);color:var(--text-muted);border:1px solid var(--card-border);}
.badge-mesa{padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;display:inline-block;}
.badge-mesa.livre{background:rgba(92,184,92,0.15);color:#5cb85c;border:1px solid rgba(92,184,92,0.3);}
.badge-mesa.ocupada{background:rgba(224,92,92,0.15);color:#e05c5c;border:1px solid rgba(224,92,92,0.3);}
.badge-mesa.reservada{background:rgba(230,168,23,0.15);color:#e6a817;border:1px solid rgba(230,168,23,0.3);}
.form-group{margin-bottom:18px;}
.form-group label{display:block;font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:7px;}
.form-control{width:100%;padding:11px 14px;background:rgba(255,255,255,0.05);border:1px solid var(--card-border);border-radius:9px;color:var(--text);font-size:0.9rem;transition:all .2s;}
.form-control:focus{outline:none;border-color:var(--accent);background:rgba(255,255,255,0.08);box-shadow:0 0 0 3px rgba(232,168,124,0.12);}
.form-control option{background:#1a1a22;}
.alert{padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:0.86rem;}
.alert-success{background:rgba(92,184,92,0.12);border:1px solid rgba(92,184,92,0.25);color:#5cb85c;}
.alert-danger{background:rgba(224,92,92,0.12);border:1px solid rgba(224,92,92,0.25);color:#e05c5c;}
.row{display:flex;flex-wrap:wrap;gap:20px;}
.col-6{flex:1;min-width:260px;}
.col-4{flex:1;min-width:200px;}
.col-3{flex:0 0 calc(25% - 15px);min-width:180px;}
.stat-card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;padding:22px;display:flex;align-items:center;gap:16px;transition:transform .2s;}
.stat-card:hover{transform:translateY(-3px);}
.stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;}
.stat-label{font-size:0.76rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;}
.stat-value{font-size:1.6rem;font-weight:800;color:var(--text);line-height:1;}
.stat-sub{font-size:0.72rem;color:var(--text-muted);margin-top:4px;}
textarea.form-control{min-height:90px;resize:vertical;}
.hamburger{display:none;background:none;border:none;color:var(--text);font-size:1.4rem;cursor:pointer;}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99;}
@media(max-width:768px){
.sidebar{transform:translateX(-100%);}
.sidebar.open{transform:translateX(0);}
.main{margin-left:0;}
.hamburger{display:block;}
.overlay.show{display:block;}
.col-3,.col-4,.col-6{flex:0 0 100%;}
.content{padding:16px;}
}
footer{text-align:center;padding:18px 0 10px;font-size:.72rem;color:#555;border-top:1px solid rgba(255,255,255,.05);margin-top:32px;}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal-box{background:#1a1a22;border:1px solid rgba(255,255,255,.1);border-radius:18px;padding:30px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;}
.modal-box h3{font-size:1.05rem;font-weight:700;margin-bottom:20px;color:var(--text);}
.text-right{text-align:right;}
.text-center{text-align:center;}
.mt-10{margin-top:10px;}
.mt-20{margin-top:20px;}
.gap-10{gap:10px;display:flex;align-items:center;}
.w-100{width:100%;}
</style>
</head>
<body>
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-inner">
      <div class="ico">🍽️</div>
      <div>
        <h2>Restaurante</h2>
        <span>Manager Pro</span>
      </div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Menu Principal</div>
    <?php foreach ($menu as $item): ?>
    <a href="<?= $item['file'] ?>" class="nav-item <?= $current_page === $item['file'] ? 'active' : '' ?>">
      <span class="nav-icon"><?= $item['icon'] ?></span>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['usuario_nome'], 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></div>
        <div class="user-role"><?= ucfirst($_SESSION['usuario_perfil']) ?></div>
      </div>
    </div>
    <a href="logout.php" class="btn-logout">⏻ Sair do Sistema</a>
  </div>
</div>
<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="hamburger" onclick="toggleSidebar()">☰</button>
      <h1><?= htmlspecialchars($page_title) ?></h1>
    </div>
    <div class="topbar-right">
      <span class="topbar-badge">🕐 <?= date('d/m/Y H:i') ?></span>
    </div>
  </div>
  <div class="content">