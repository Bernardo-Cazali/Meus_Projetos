<?php
session_start();
require_once '../db.php';
require_once '../upload_helper.php';

if (!isset($_SESSION['logado'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_loja = trim($_POST['nome_loja']);
    $whatsapp = trim($_POST['whatsapp']);
    $instagram = trim($_POST['instagram']);
    $endereco = trim($_POST['endereco']);

    $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);

    $whatsapp_valido = (strlen($whatsapp) === 13);

    if (!empty($nome_loja) && $whatsapp_valido && !empty($instagram) && !empty($endereco)) {
        $stmt_atual = $pdo->query('SELECT logo FROM configuracoes WHERE id = 1');
        $logo_atual = $stmt_atual->fetchColumn();
        $logo_final = $logo_atual;

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $resultado_upload = processarUploadImagem($_FILES['logo'], '../uploads', 'logo_');

            if ($resultado_upload['sucesso']) {
                $logo_final = 'uploads/' . $resultado_upload['caminho'];
            } else {
                $mensagem = $resultado_upload['erro'];
                $status = 'erro';
            }
        }

        if (empty($mensagem)) {
            try {
                $stmt = $pdo->prepare('UPDATE configuracoes SET nome_loja = ?, whatsapp = ?, instagram = ?, endereco = ?, logo = ? WHERE id = 1');
                $stmt->execute([$nome_loja, $whatsapp, $instagram, $endereco, $logo_final]);
                $mensagem = 'Configurações atualizadas com sucesso!';
                $status = 'sucesso';
            } catch (\PDOException $e) {
                error_log($e->getMessage());
                $mensagem = 'Erro interno ao salvar no banco.';
                $status = 'erro';
            }
        }
    } else {
        if (!$whatsapp_valido) {
            $mensagem = 'O WhatsApp deve estar completo, no formato +55 (DDD) 99999-9999.';
        } else {
            $mensagem = 'Por favor, preencha todos os campos.';
        }
        $status = 'erro';
    }
}

$stmt = $pdo->query('SELECT * FROM configuracoes WHERE id = 1');
$config = $stmt->fetch();

function formatarWhatsappExibicao(?string $numero): string
{
    if (empty($numero)) {
        return '';
    }
    $d = preg_replace('/\D/', '', $numero);
    if (strlen($d) < 4) {
        return $d;
    }
    $pais = substr($d, 0, 2);
    $ddd = substr($d, 2, 2);
    $resto = substr($d, 4);
    if (strlen($resto) > 4) {
        $resto = substr($resto, 0, -4) . '-' . substr($resto, -4);
    }
    return "+{$pais} ({$ddd}) {$resto}";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações da Loja | Luz & Aroma</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        .navbar { background-color: #4A3525; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { margin: 0; font-size: 1.4rem; }
        .navbar a { color: #f8f9fa; text-decoration: none; padding: 5px 10px; border-radius: 4px; background: rgba(255,255,255,0.1); }
        .container { max-width: 600px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        h2 { color: #4A3525; margin-top: 0; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-salvar { background-color: #4A3525; color: white; padding: 12px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%; font-size: 1rem; }
        .btn-salvar:hover { background-color: #3d2b1e; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .alert-sucesso { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-erro { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .logo-preview { max-width: 150px; max-height: 150px; display: block; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ddd; object-fit: contain; background: #fafafa; padding: 8px; box-sizing: border-box; }
        .dica { font-size: 0.8rem; color: #888; margin-top: 4px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <h1>Painel Luz & Aroma</h1>
        <a href="dashboard.php">Voltar ao Painel</a>
    </nav>

    <div class="container">
        <h2>Configurações Gerais da Loja</h2>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $status ?>"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome_loja">Nome da Loja</label>
                <input type="text" id="nome_loja" name="nome_loja" value="<?= htmlspecialchars($config['nome_loja']) ?>" required>
            </div>

            <div class="form-group">
                <label>Logo da Loja</label>
                <?php if (!empty($config['logo'])): ?>
                    <img src="<?= BASE_URL . '/' . htmlspecialchars($config['logo']) ?>" class="logo-preview" alt="Logo atual">
                <?php else: ?>
                    <p class="dica">Nenhuma logo cadastrada ainda — o nome da loja é exibido no lugar.</p>
                <?php endif; ?>
                <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
                <p class="dica">Formatos aceitos: JPG, PNG ou WEBP. Tamanho máximo: 5MB. Deixe em branco para manter a logo atual.</p>
            </div>
            
            <div class="form-group">
                <label for="whatsapp">WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars(formatarWhatsappExibicao($config['whatsapp'])) ?>" placeholder="+55 (47) 99999-9999" required>
                <p class="dica">Digite com DDD. A formatação é aplicada automaticamente.</p>
            </div>

            <div class="form-group">
                <label for="instagram">Instagram (usuário com @)</label>
                <input type="text" id="instagram" name="instagram" value="<?= htmlspecialchars($config['instagram']) ?>" placeholder="Ex: @luz_aroma" required>
            </div>

            <div class="form-group">
                <label for="endereco">Endereço da Loja</label>
                <textarea id="endereco" name="endereco" rows="3" required><?= htmlspecialchars($config['endereco']) ?></textarea>
            </div>

            <button type="submit" class="btn-salvar">Salvar Configurações</button>
        </form>
    </div>

    <script>
        const campoWhats = document.getElementById('whatsapp');

        function aplicarMascaraWhats(valor) {
            const digitos = valor.replace(/\D/g, '').slice(0, 13); 
            let resultado = '';

            if (digitos.length > 0) resultado = '+' + digitos.slice(0, 2);
            if (digitos.length > 2) resultado += ' (' + digitos.slice(2, 4);
            if (digitos.length >= 4) resultado += ')';
            if (digitos.length > 4) resultado += ' ' + digitos.slice(4, 9);
            if (digitos.length > 9) resultado += '-' + digitos.slice(9, 13);

            return resultado;
        }

        campoWhats.addEventListener('input', (e) => {
            e.target.value = aplicarMascaraWhats(e.target.value);
        });
    </script>

</body>
</html>