<?php
session_start();
require_once '../db.php';
require_once '../upload_helper.php';

if (!isset($_SESSION['logado'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = trim($_POST['preco']);
    $texto_whats = trim($_POST['texto_whats']);
    $url_imagem = trim($_POST['url_imagem']);
    $categoria = $_POST['categoria'] ?? '';
    $estoque = (int)($_POST['estoque'] ?? 0);

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nome), '-'));
    $preco = str_replace(',', '.', $preco);

    if (!empty($nome) && !empty($descricao) && !empty($preco) && $estoque >= 0) {
        $imagem_final = '';

        if (isset($_FILES['arquivo_imagem']) && $_FILES['arquivo_imagem']['error'] === UPLOAD_ERR_OK) {
            
            $resultado_upload = processarUploadImagem($_FILES['arquivo_imagem'], '../uploads', 'vela_');
            if ($resultado_upload['sucesso']) {
                $imagem_final = 'uploads/' . $resultado_upload['caminho'];
            } else {
                $mensagem = $resultado_upload['erro'];
            }
        } elseif (!empty($url_imagem)) {
            if (filter_var($url_imagem, FILTER_VALIDATE_URL)) {
                $imagem_final = $url_imagem;
            } else {
                $mensagem = 'A URL da imagem inserida não é válida.';
            }
        }

        if (!empty($imagem_final) && empty($mensagem)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO produtos (nome, descricao, preco, url_imagem, texto_whats, categoria, estoque, slug) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$nome, $descricao, $preco, $imagem_final, $texto_whats, $categoria, $estoque, $slug]);
                
                header('Location: dashboard.php');
                exit;
            } catch (\PDOException $e) {
                error_log($e->getMessage());
                $mensagem = 'Erro interno ao salvar no banco de dados.';
            }
        } elseif (empty($mensagem)) {
            $mensagem = 'Por favor, envie uma imagem ou insira uma URL.';
        }
    } else {
        $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Nova Vela | Luz & Aroma</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
        .form-container { max-width: 600px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        h2 { color: #8C6239; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-salvar { background-color: #25D366; color: white; padding: 12px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 1rem; transition: background 0.3s; }
        .btn-salvar:hover { background-color: #1fba58; }
        .btn-cancelar { color: #666; text-decoration: none; margin-left: 15px; }
        .alert { padding: 10px; background-color: #f2dede; color: #a94442; border-radius: 4px; margin-bottom: 15px; }
        .opcao-aba { display: none; margin-top: 10px; padding: 10px; background: #fdfbf7; border: 1px dashed #d4a373; border-radius: 4px; }
        .opcao-aba.ativa { display: block; }
        .dica { font-size: 0.8rem; color: #888; margin-top: 4px; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Cadastrar Nova Vela</h2>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <form action="cadastrar.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome da Vela *</label>
                <input type="text" id="nome" name="nome" value="<?= isset($nome) ? htmlspecialchars($nome) : '' ?>" required placeholder="Ex: Vela Aromática de Lavanda">
            </div>
            
            <div class="form-group">
                <label for="preco">Preço (R$) *</label>
                <input type="text" id="preco" name="preco" value="<?= isset($_POST['preco']) ? htmlspecialchars($_POST['preco']) : '' ?>" required placeholder="Ex: 39,90">
            </div>

            <div class="form-group">
                <label for="tipo_imagem">Como deseja adicionar a imagem? *</label>
                <select id="tipo_imagem" onchange="alternarOrigemImagem()">
                    <option value="upload">Fazer upload do Computador (Recomendado)</option>
                    <option value="url">Colar link (URL) da Internet</option>
                </select>
            </div>

            <div id="aba-upload" class="opcao-aba ativa">
                <label style="font-weight: bold; color: #555;">Selecione a Imagem do PC *</label>
                <input type="file" id="arquivo_imagem" name="arquivo_imagem" accept="image/png,image/jpeg,image/webp">
                <p class="dica">Formatos aceitos: JPG, PNG ou WEBP. Tamanho máximo: 5MB.</p>
            </div>

            <div id="aba-url" class="opcao-aba">
                <label style="font-weight: bold; color: #555;">Cole a URL da Imagem *</label>
                <input type="url" id="url_imagem" name="url_imagem" value="<?= isset($url_imagem) ? htmlspecialchars($url_imagem) : '' ?>" placeholder="https://exemplo.com/foto.jpg">
            </div>

            <div class="form-group">
                <label for="categoria">Categoria *</label>
                <select id="categoria" name="categoria" required>
                    <option value="Relaxantes" <?= (isset($categoria) && $categoria === 'Relaxantes') ? 'selected' : '' ?>>Relaxantes</option>
                    <option value="Doces" <?= (isset($categoria) && $categoria === 'Doces') ? 'selected' : '' ?>>Doces</option>
                    <option value="Cítricas" <?= (isset($categoria) && $categoria === 'Cítricas') ? 'selected' : '' ?>>Cítricas</option>
                </select>
            </div>

            <div class="form-group">
                <label for="estoque">Quantidade em Estoque *</label>
                <input type="number" id="estoque" name="estoque" min="0" value="<?= isset($_POST['estoque']) ? (int)$_POST['estoque'] : '10' ?>" required>
             </div>

            <div class="form-group" style="margin-top: 15px;">
                <label for="texto_whats">Texto personalizado do WhatsApp (Opcional)</label>
                <input type="text" id="texto_whats" name="texto_whats" value="<?= isset($texto_whats) ? htmlspecialchars($texto_whats) : '' ?>" placeholder="Ex: Olá, gostaria de encomendar a vela de Lavanda!">
            </div>

            <div class="form-group">
                <label for="descricao">Descrição da Vela *</label>
                <textarea id="descricao" name="descricao" rows="4" required placeholder="Conte detalhes sobre o aroma, tamanho, tempo de queima..."><?= isset($descricao) ? htmlspecialchars($descricao) : '' ?></textarea>
            </div>

            <button type="submit" class="btn-salvar">Cadastrar Vela</button>
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

            if (tipo === 'upload') {
                abaUpload.classList.add('ativa');
                abaUrl.classList.remove('ativa');
                inputUrl.value = '';
            } else {
                abaUrl.classList.add('ativa');
                abaUpload.classList.remove('ativa');
                inputUpload.value = '';
            }
        }
    </script>
</body>
</html>