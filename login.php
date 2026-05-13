<?php
session_start();

require_once 'functions.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  try {

    $conn = db();

    $sql = "
            SELECT *
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ";

    $stmt =
      $conn->prepare($sql);

    $stmt->execute([
      ':email' =>
      $_POST['email']
    ]);

    $user =
      $stmt->fetch(
        PDO::FETCH_ASSOC
      );

    if (
      $user &&
      password_verify(
        $_POST['senha'],
        $user['senha']
      )
    ) {

      $_SESSION['user']
        = $user;

      header(
        'Location: index.php'
      );
      exit;
    }

    $erro =
      "Login inválido";
  } catch (Exception $e) {

    $erro =
      $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Restaurante Manager</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
      font-family: 'Segoe UI', sans-serif;
    }

    .login-wrap {
      width: 100%;
      max-width: 420px;
      padding: 20px;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 48px 40px;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
    }

    .logo {
      text-align: center;
      margin-bottom: 32px;
    }

    .logo-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, #e8a87c, #c97b4b);
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      margin: 0 auto 14px;
      box-shadow: 0 8px 24px rgba(232, 168, 124, 0.3);
    }

    .logo h1 {
      color: #fff;
      font-size: 1.6rem;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .logo p {
      color: rgba(255, 255, 255, 0.5);
      font-size: 0.82rem;
      margin-top: 4px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.82rem;
      font-weight: 600;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-group input {
      width: 100%;
      padding: 13px 16px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 10px;
      color: #fff;
      font-size: 0.95rem;
      transition: all .3s;
    }

    .form-group input:focus {
      outline: none;
      border-color: #e8a87c;
      background: rgba(255, 255, 255, 0.12);
      box-shadow: 0 0 0 3px rgba(232, 168, 124, 0.15);
    }

    .form-group input::placeholder {
      color: rgba(255, 255, 255, 0.3);
    }

    btn-login {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #e8a87c, #c97b4b);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all .3s;
      margin-top: 8px;
      letter-spacing: 0.5px;
    }

    .btn-login {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #e8a87c, #c97b4b);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all .3s;
      margin-top: 8px;
      letter-spacing: 0.5px;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(232, 168, 124, 0.4);
    }

    .erro {
      background: rgba(220, 53, 69, 0.15);
      border: 1px solid rgba(220, 53, 69, 0.3);
      color: #ff6b7a;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 0.86rem;
      margin-bottom: 20px;
      text-align: center;
    }

    .hint {
      margin-top: 20px;
      padding: 14px;
      background: rgba(255, 255, 255, 0.04);
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .hint p {
      color: rgba(255, 255, 255, 0.45);
      font-size: 0.75rem;
      text-align: center;
      line-height: 1.6;
    }

    .hint strong {
      color: rgba(255, 255, 255, 0.65);
    }

    footer {
      text-align: center;
      padding: 18px 0 10px;
      font-size: .72rem;
      color: rgba(255, 255, 255, 0.3);
      margin-top: 24px;
    }
  </style>
</head>

<body>
  <div class="login-wrap">
    <div class="login-card">
      <div class="logo">
        <div class="logo-icon">🍽️</div>
        <h1>Restaurante Manager</h1>
        <p>Sistema de Gestão Completo</p>
      </div>
      <?php if ($erro): ?>
        <div class="erro">⚠️ <?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <form method="POST" action="login.php">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="seu@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Senha</label>
          <input type="password" name="senha" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-login">🔐 Entrar no Sistema</button>
      </form>
      <div class="hint">
        <p><strong>Admin:</strong> admin@restaurante.com / admin123</p>
        <p><strong>Garçom:</strong> garcom@restaurante.com / garcom123</p>
      </div>
    </div>
    <footer>Desenvolvido por: EDCLECIO</footer>
  </div>
</body>

</html>