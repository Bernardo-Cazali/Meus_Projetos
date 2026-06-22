<?php
session_start();
require_once 'db.php';

function montarSrcImagem($valor) {
    if (empty($valor)) {
        return 'https://via.placeholder.com/600x500?text=Sem+Imagem';
    }
    if (preg_match('/^https?:\/\//i', $valor)) {
        return $valor;
    }
    return BASE_URL . '/uploads/' . basename($valor);
}

if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$slug = $_GET['slug'];

$stmt = $pdo->prepare('SELECT * FROM produtos WHERE slug = ?');
$stmt->execute([$slug]);
$produto = $stmt->fetch();

if (!$produto) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$stmt_conf = $pdo->query('SELECT * FROM configuracoes WHERE id = 1');
$config_loja = $stmt_conf->fetch();

$whatsapp_loja = $config_loja['whatsapp']; 
$texto_base = !empty($produto['texto_whats']) ? $produto['texto_whats'] : "Olá! Gostaria de encomendar a vela: " . $produto['nome'];
$link_whatsapp = "https://api.whatsapp.com/send?phone=" . $whatsapp_loja . "&text=" . urlencode($texto_base);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produto['nome']) ?> | <?= htmlspecialchars($config_loja['nome_loja']) ?></title>
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
    
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #fdfbf7; color: #4A3525; margin: 0; padding: 0; }
        .voltar { display: inline-block; margin: 20px 0; color: #8C6239; text-decoration: none; font-weight: bold; }
        .container-produto { max-width: 1000px; margin: 40px auto; padding: 20px; display: table; width: 100%; box-sizing: border-box; }
        .coluna-img, .coluna-info { display: table-cell; vertical-align: top; width: 50%; padding: 20px; box-sizing: border-box; }
        .produto-img { width: 100%; max-height: 500px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { font-size: 2.2rem; color: #4A3525; margin-top: 0; }
        .categoria-tag { display: inline-block; background-color: #EADCC9; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; margin-bottom: 15px; }
        .preco { font-size: 1.8rem; font-weight: bold; color: #8C6239; margin: 20px 0; }
        .descricao { line-height: 1.6; color: #666; margin-bottom: 30px; }
        .btn-comprar { display: inline-block; background-color: #25D366; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(37,211,102,0.3); }
        .btn-comprar:hover { background-color: #1fba58; }
        .estoque-status { margin-top: 15px; font-weight: bold; }
        .disponivel { color: #25D366; }
        .esgotado { color: #d9534f; }
    </style>
</head>
<body>

    <div style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
        <a href="<?= BASE_URL ?>/index.php" class="voltar">← Voltar para a Loja</a>
    </div>

    <div class="container-produto">
        <div class="coluna-img">
            <img src="<?= htmlspecialchars(montarSrcImagem($produto['url_imagem'])) ?>" class="produto-img" alt="<?= htmlspecialchars($produto['nome']) ?>">
        </div>

        <div class="coluna-info">
            <span class="categoria-tag"><?= htmlspecialchars($produto['categoria']) ?></span>
            <h1><?= htmlspecialchars($produto['nome']) ?></h1>
            
            <div class="preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
            
            <div class="descricao">
                <?= nl2br(htmlspecialchars($produto['descricao'])) ?>
            </div>

            <div class="estoque-status">
                <?php if ($produto['estoque'] > 0): ?>
                    <span class="disponivel">✓ Em estoque (<?= $produto['estoque'] ?> unidades disponíveis)</span>
                    <div style="margin-top: 25px;">
                        <a href="<?= $link_whatsapp ?>" target="_blank" class="btn-comprar">Encomendar pelo WhatsApp</a>
                    </div>
                <?php else: ?>
                    <span class="esgotado">✕ Esgotado no momento</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>