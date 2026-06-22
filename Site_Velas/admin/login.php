<?php
session_start();
require_once '../db.php';

if (isset($_SESSION['logado'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $senha = trim($_POST['senha']);

    if (!empty($usuario) && !empty($senha)) {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {

            session_regenerate_id(true);

            $_SESSION['logado'] = true;
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['usuario'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'Usuário ou senha incorretos.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo | Luz & Aroma</title>
    <link rel="stylesheet" href="../style.css"> 
    <style>
        .login-body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #FDFBF7; }
        .login-card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border-top: 5px solid #4A3525; }
        .login-card h2 { color: #4A3525; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; font-weight: 600; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-login { width: 100%; background: #4A3525; color: white; border: none; padding: 12px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .btn-login:hover { background: #352418; }
        .erro-msg { color: #d9534f; background: #f2dede; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; }
    </style>
</head>
<body class="login-body">

    <div class="login-card">
        <h2>Luz & Aroma</h2>
        
        <?php if (!empty($erro)): ?>
            <div class="erro-msg"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" required autofocus>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn-login">Entrar no Painel</button>
        </form>
    </div>

</body>
</html>