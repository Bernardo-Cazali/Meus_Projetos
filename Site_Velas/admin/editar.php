<?php
session_start();
require_once '../db.php';
require_once '../upload_helper.php';

if (!isset($_SESSION['logado'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
    $stmt->execute([$id]);
    $produto = $stmt->fetch();

    if (!$produto) {
        header('Location: dashboard.php');
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}

$imagem_atual_e_url = preg_match('/^https?:\/\//i', $produto['url_imagem']) === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = trim($_POST['preco']);
    $texto_whats = trim($_POST['texto_whats']);
    $url_imagem = trim($_POST['url_imagem']);
    $categoria = $_POST['categoria'] ?? '';
    $estoque = (int)($_POST['estoque'] ?? 0);

    $preco = str_replace(',', '.', $preco);

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nome), '-'));

    $imagem_final = $produto['url_imagem'];

    if (isset($_FILES['arquivo_imagem']) && $_FILES['arquivo_imagem']['error'] === UPLOAD_ERR_OK) {
        $resultado_upload = processarUploadImagem($_FILES['arquivo_imagem'], '../uploads', 'vela_');

        if ($resultado_upload['sucesso']) {
            $imagem_final = 'uploads/' . $resultado_upload['caminho'];
        } else {
            $mensagem = $resultado_upload['erro'];
        }
    } elseif (!empty($url_imagem) && $url_imagem !== $produto['url_imagem']) {
        if (filter_var($url_imagem, FILTER_VALIDATE_URL)) {
            $imagem_final = $url_imagem;
        } else {
            $mensagem = 'A URL da imagem inserida não é válida.';
        }
    }

    if (!empty($nome) && !empty($descricao) && !empty($preco) && !empty($imagem_final) && $estoque >= 0 && empty($mensagem)) {
        try {
            $stmt = $pdo->prepare('UPDATE produtos SET nome = ?, descricao = ?, preco = ?, url_imagem = ?, texto_whats = ?, categoria = ?, estoque = ?, slug = ? WHERE id = ?');
            $stmt->execute([$nome, $descricao, $preco, $imagem_final, $texto_whats, $categoria, $estoque, $slug, $id]);
            
            header('Location: dashboard.php');
            exit;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            $mensagem = 'Erro interno ao atualizar no banco de dados.';
        }
    } elseif (empty($mensagem)) {
        $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vela | Luz & Aroma</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
        .form-container { max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        h2 { color: #8C6239; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-salvar { background-color: #D4A373; color: white; padding: 12px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 1rem; transition: background 0.3s; }
        .btn-salvar:hover { background-color: #c39262; }
        .btn-cancelar { color: #666; text-decoration: none; margin-left: 15px; }
        .alert { padding: 10px; background-color: #f2dede; color: #a94442; border-radius: 4px; margin-bottom: 15px; }
        .opcao-aba { display: none; margin-top: 10px; padding: 10px; background: #fdfbf7; border: 1px dashed #d4a373; border-radius: 4px; }
        .opcao-aba.ativa { display: block; }
        .imagem-atual { max-width: 150px; max-height: 150px; display: block; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ddd; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Editar Vela: <?= htmlspecialchars($produto['nome']) ?></h2>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <form action="editar.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome do Produto *</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="preco">Preço (R$) *</label>
                <input type="text" id="preco" name="preco" value="<?= htmlspecialchars($produto['preco']) ?>" required>
            </div>

            <div class="form-group">
                <label>Imagem Atual</label>
                <img src="<?= preg_match('/^https?:\/\//i', $produto['url_imagem']) ? htmlspecialchars($produto['url_imagem']) : BASE_URL . '/uploads/' . htmlspecialchars(basename($produto['url_imagem'])) ?>" class="imagem-atual" alt="Imagem atual">
            </div>

            <div class="form-group">
                <label for="tipo_imagem">Deseja trocar a imagem?</label>
                <select id="tipo_imagem" onchange="alternarOrigemImagem()">
                    <option value="manter" <?= !isset($_POST['tipo_imagem']) ? 'selected' : '' ?>>Manter imagem atual</option>
                    <option value="upload">Fazer upload do Computador</option>
                    <option value="url">Colar link (URL) da Internet</option>
                </select>
            </div>

            <div id="aba-upload" class="opcao-aba">
                <label style="font-weight: bold; color: #555;">Selecione a nova imagem do PC</label>
                <input type="file" id="arquivo_imagem" name="arquivo_imagem" accept="image/*">
            </div>

            <div id="aba-url" class="opcao-aba">
                <label style="font-weight: bold; color: #555;">Cole a nova URL da imagem</label>
                <input type="url" id="url_imagem" name="url_imagem" value="<?= $imagem_atual_e_url ? htmlspecialchars($produto['url_imagem']) : '' ?>" placeholder="https://exemplo.com/foto.jpg">
            </div>

            <div class="form-group">
                <label for="categoria">Categoria *</label>
                <select id="categoria" name="categoria" required>
                    <option value="Relaxantes" <?= ($produto['categoria'] === 'Relaxantes') ? 'selected' : '' ?>>Relaxantes</option>
                    <option value="Doces" <?= ($produto['categoria'] === 'Doces') ? 'selected' : '' ?>>Doces</option>
                    <option value="Cítricas" <?= ($produto['categoria'] === 'Cítricas') ? 'selected' : '' ?>>Cítricas</option>
                </select>
            </div>

            <div class="form-group">
                <label for="estoque">Quantidade em Estoque *</label>
                <input type="number" id="estoque" name="estoque" min="0" value="<?= (int)$produto['estoque'] ?>" required>
             </div>

            <div class="form-group">
                <label for="texto_whats">Texto personalizado do WhatsApp (Opcional)</label>
                <input type="text" id="texto_whats" name="texto_whats" value="<?= htmlspecialchars($produto['texto_whats'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="descricao">Descrição Curta *</label>
                <textarea id="descricao" name="descricao" rows="4" required><?= htmlspecialchars($produto['descricao']) ?></textarea>
            </div>

            <button type="submit" class="btn-salvar">Salvar Alterações</button>
            <a href="dashboard.php" class="btn-cancelar">Cancelar</a>
        </form>
    </div>

    <script>
        function alternarOrigemImagem() {
            const tipo = document.getElementById('tipo_imagem').value;
            const abaUpload = document.getElementById('aba-upload');
            const abaUrl = document.getElementById('aba-url');
            const inputUpload = document.getElementById('arquivo_imagem');
            const inputUrl = document.getElementById('url_imagem');

            abaUpload.classList.remove('ativa');
            abaUrl.classList.remove('ativa');

            if (tipo === 'upload') {
                abaUpload.classList.add('ativa');
                inputUrl.value = '';
            } else if (tipo === 'url') {
                abaUrl.classList.add('ativa');
                inputUpload.value = '';
            } else {
                inputUpload.value = '';
                inputUrl.value = '';
            }
        }
    </script>
</body>
</html>