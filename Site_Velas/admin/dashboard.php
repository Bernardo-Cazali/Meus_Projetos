<?php
session_start(); 
require_once '../db.php';

if (!isset($_SESSION['logado'])) {
    header('Location: login.php');
    exit;
}

function montarSrcImagem($valor) {
    if (empty($valor)) {
        return 'https://via.placeholder.com/50x50?text=Sem+Foto';
    }
    if (preg_match('/^https?:\/\//i', $valor)) {
        return $valor;
    }
    return BASE_URL . '/uploads/' . basename($valor);
}

if (isset($_GET['acao_dep']) && isset($_GET['id_dep'])) {
    $id_dep = (int)$_GET['id_dep'];
    $acao = $_GET['acao_dep'];

    if ($id_dep > 0) {
        if ($acao === 'aprovar') {
            $stmt = $pdo->prepare('UPDATE depoimentos SET aprovado = 1 WHERE id = ?');
            $stmt->execute([$id_dep]);
        } elseif ($acao === 'recusar') {
            $stmt = $pdo->prepare('UPDATE depoimentos SET aprovado = 2 WHERE id = ?');
            $stmt->execute([$id_dep]);
        }
    }
    header('Location: dashboard.php');
    exit;
}

$stmt_pendentes = $pdo->query('SELECT * FROM depoimentos WHERE aprovado = 0 ORDER BY data_envio DESC');
$depoimentos_pendentes = $stmt_pendentes->fetchAll();

$stmt = $pdo->query('SELECT * FROM produtos ORDER BY id DESC');
$produtos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Luz & Aroma</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        .navbar { background-color: #4A3525; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { margin: 0; font-size: 1.4rem; }
        .navbar a { color: #f8f9fa; text-decoration: none; padding: 5px 10px; border-radius: 4px; background: rgba(255,255,255,0.1); }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .header-acoes { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-adicionar { background-color: #25D366; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold; }
        .btn-adicionar:hover { background-color: #1fba58; }
        
        .tabela-produtos { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .tabela-produtos th, .tabela-produtos td { padding: 15px; text-align: left; border-bottom: 1px solid #eeeeee; }
        .tabela-produtos th { background-color: #EADCC9; color: #4A3525; font-weight: bold; }
        .img-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        
        .btn-editar { color: #337ab7; text-decoration: none; margin-right: 15px; font-weight: bold; }
        .btn-deletar { color: #d9534f; text-decoration: none; font-weight: bold; }
        .btn-editar:hover, .btn-deletar:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <nav class="navbar">
        <h1>Painel Luz & Aroma</h1>
        <div>
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>! </span>
            <a href="configuracoes.php" style="margin-right: 10px; background: #8C6239;">⚙️ Configurações</a>
            <a href="logout.php">Sair</a>
        </div>
    </nav>

    <div class="container">
        <div class="header-acoes">
            <h2>Gerenciamento de Velas</h2>
            <a href="cadastrar.php" class="btn-adicionar">+ Nova Vela</a>
        </div>

        <table class="tabela-produtos">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Descrição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($produtos) > 0): ?>
                    <?php foreach ($produtos as $p): ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars(montarSrcImagem($p['url_imagem'])) ?>" class="img-preview" alt="Vela"></td>
                            <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                            <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars(substr($p['descricao'], 0, 60)) ?>...</td>
                            <td>
                                <a href="editar.php?id=<?= (int)$p['id'] ?>" class="btn-editar">Editar</a>
                                <a href="deletar.php?id=<?= (int)$p['id'] ?>" class="btn-deletar" onclick="return confirm('Tem certeza que deseja excluir este produto?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #999;">Nenhum produto cadastrado ainda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2 style="margin-top: 50px; margin-bottom: 20px;">Moderação de Depoimentos</h2>

        <table class="tabela-produtos" style="margin-bottom: 50px;">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Nota</th>
                    <th>Mensagem</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($depoimentos_pendentes) > 0): ?>
                    <?php foreach ($depoimentos_pendentes as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['nome']) ?></strong></td>
                            <td style="color: #FFD700;"><?= str_repeat('★', (int)$d['estrelas']) ?></td>
                            <td><?= htmlspecialchars($d['texto']) ?></td>
                            <td>
                                <a href="dashboard.php?acao_dep=aprovar&id_dep=<?= (int)$d['id'] ?>" style="color: #25D366; font-weight: bold; text-decoration: none; margin-right: 15px;">Aprovar</a>
                                <a href="dashboard.php?acao_dep=recusar&id_dep=<?= (int)$d['id'] ?>" style="color: #d9534f; font-weight: bold; text-decoration: none;" onclick="return confirm('Deseja recusar este depoimento?')">Recusar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #999;">Nenhum depoimento pendente de moderação.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>