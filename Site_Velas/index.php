<?php
session_start();
require_once 'db.php'; 

function montarSrcImagem($valor) {
    if (empty($valor)) {
        return 'https://via.placeholder.com/400x300?text=Sem+Imagem';
    }
    if (preg_match('/^https?:\/\//i', $valor)) {
        return $valor;
    }
    return BASE_URL . '/uploads/' . basename($valor);
}

$stmt_conf = $pdo->query('SELECT * FROM configuracoes WHERE id = 1');
$config_loja = $stmt_conf->fetch();

$stmt_cats = $pdo->query('SELECT DISTINCT categoria FROM produtos WHERE categoria != "" ORDER BY categoria ASC');
$categorias_existentes = $stmt_cats->fetchAll(PDO::FETCH_COLUMN); 

$categoria_selecionada = isset($_GET['cat']) ? $_GET['cat'] : '';

if (!empty($categoria_selecionada) && in_array($categoria_selecionada, $categorias_existentes)) {
    $stmt_prod = $pdo->prepare('SELECT * FROM produtos WHERE categoria = ? ORDER BY id DESC');
    $stmt_prod->execute([$categoria_selecionada]);
} else {
    $stmt_prod = $pdo->query('SELECT * FROM produtos ORDER BY id DESC');
    $categoria_selecionada = ''; 
}
$produtos = $stmt_prod->fetchAll();

$stmt_dep = $pdo->query('SELECT * FROM depoimentos WHERE aprovado = 1 ORDER BY data_envio DESC');
$depoimentos = $stmt_dep->fetchAll();

$msg_feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_feedback'])) {
    $nome_cliente = trim($_POST['nome_cliente']);
    $estrelas = (int)$_POST['estrelas'];
    $texto_cliente = trim($_POST['texto_cliente']);

    if (!empty($nome_cliente) && $estrelas >= 1 && !empty($texto_cliente)) {
        $stmt_cad = $pdo->prepare('INSERT INTO depoimentos (nome, estrelas, texto) VALUES (?, ?, ?)');
        $stmt_cad->execute([$nome_cliente, $estrelas, $texto_cliente]);
        $msg_feedback = 'Obrigado! Seu depoimento foi enviado e passará por moderação antes de aparecer no site.';
    } else {
        $msg_feedback = 'Por favor, preencha todos os campos para enviar sua avaliação.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config_loja['nome_loja']) ?> | Velas Artesanais</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
    <meta name="description" content="Velas aromáticas artesanais feitas à mão com cera vegetal e essências premium. Traga aconchego e bem-estar para o seu ambiente. Encomende pelo WhatsApp.">
    <meta name="keywords" content="velas aromáticas, velas artesanais, bem-estar, decoração, luz e aroma, velas perfumadas">
    <link rel="shortcut icon" href="https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=32" type="image/x-icon">
</head>
<body>

    <header>
        <div class="logo">
            <?php if (!empty($config_loja['logo'])): ?>
                <img src="<?= BASE_URL . '/' . htmlspecialchars($config_loja['logo']) ?>" alt="<?= htmlspecialchars($config_loja['nome_loja']) ?>" class="logo-imagem">
            <?php endif; ?>
            <?= htmlspecialchars($config_loja['nome_loja']) ?>
        </div>
        
        <nav>
            <ul>
                <li><a href="#inicio">Início</a></li>
                <li><a href="#produtos">Produtos</a></li>
                <li><a href="#sobre">Sobre Nós</a></li>
                <li><a href="#contato">Contato</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section id="inicio" class="hero">
            <div class="hero-content">
                <h1>Transforme seu ambiente com aromas únicos</h1>
                <p>Velas artesanais feitas à mão para deixar sua casa mais aconchegante.</p>
                <a href="#produtos" class="btn-hero">Conheça nossas velas</a>
            </div>
        </section>

        <section class="beneficios">
            <div class="beneficio-item">
                <i class="fas fa-candles"></i>
                <span>🕯️ Produção artesanal</span>
            </div>
            <div class="beneficio-item">
                <i class="fas fa-leaf"></i>
                <span>🌱 Materiais selecionados</span>
            </div>
            <div class="beneficio-item">
                <i class="fas fa-truck"></i>
                <span>🚚 Entrega rápida</span>
            </div>
            <div class="beneficio-item">
                <i class="fas fa-heart"></i>
                <span>❤️ Feito com carinho</span>
            </div>
        </section>

        <section class="produtos" id="produtos">
            <h2>Nossas Velas Aromáticas</h2>
            
            <div class="filtros-categoria" style="text-align: center; margin-bottom: 30px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/index.php#produtos" 
                   style="text-decoration: none; padding: 8px 18px; border-radius: 20px; font-weight: bold; border: 1px solid #8C6239; 
                          background-color: <?= empty($categoria_selecionada) ? '#8C6239' : 'transparent' ?>; 
                          color: <?= empty($categoria_selecionada) ? 'white' : '#8C6239' ?>;">
                   Todas
                </a>
                
                <?php foreach ($categorias_existentes as $cat): ?>
                    <a href="<?= BASE_URL ?>/index.php?cat=<?= urlencode($cat) ?>#produtos" 
                       style="text-decoration: none; padding: 8px 18px; border-radius: 20px; font-weight: bold; border: 1px solid #8C6239; 
                              background-color: <?= $categoria_selecionada === $cat ? '#8C6239' : 'transparent' ?>; 
                              color: <?= $categoria_selecionada === $cat ? 'white' : '#8C6239' ?>;">
                       <?= htmlspecialchars($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="produtos-container" id="produtos-container">
            
            <?php if (count($produtos) > 0): ?>
                <?php foreach ($produtos as $p): 
                    // AJUSTADO: Puxando o número direto das configurações dinâmicas
                    $numeroWhatsApp = $config_loja['whatsapp']; 
                    $textoBase = !empty($p['texto_whats']) ? $p['texto_whats'] : "Olá! Gostaria de encomendar a vela " . $p['nome'] . ".";
                    $linkWhats = "https://wa.me/" . $numeroWhatsApp . "?text=" . urlencode($textoBase);
                ?>
                    <div class="produto-card">
                        <a href="<?= BASE_URL ?>/produto/<?= $p['slug'] ?>" style="text-decoration: none; color: inherit;">
                            <img src="<?= htmlspecialchars(montarSrcImagem($p['url_imagem'])) ?>" alt="<?= htmlspecialchars($p['nome']) ?>">
                            <h3><?= htmlspecialchars($p['nome']) ?></h3>
                        </a>
                        
                        <p class="descricao"><?= htmlspecialchars($p['descricao']) ?></p>
                        <span class="preco">R$ <?= number_format($p['preco'], 2, ',', '.') ?></span>
                        
                        <?php if ($p['estoque'] > 0): ?>
                            <a href="<?= $linkWhats ?>" target="_blank" class="btn-comprar">Pedir agora</a>
                        <?php else: ?>
                            <button class="btn-comprar" style="background-color: #d9534f; cursor: not-allowed;" disabled>Esgotado</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; grid-column: 1/-1; color: #666;">
                    Nenhuma vela disponível no momento. Volte logo!
                </p>
            <?php endif; ?>

            </div>
        </section>

        <section id="sobre" class="sobre">
            <div class="sobre-conteudo">
                <h2>Sobre Nós</h2>
                <p>A Luz & Aroma nasceu da vontade de transformar pequenos momentos em experiências especiais. Cada vela é produzida artesanalmente com cuidado e carinho.</p>
            </div>
            <div class="sobre-imagem">
                <img src="https://plus.unsplash.com/premium_photo-1680098056989-7045096b603b?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Imagem da produção de velas artesanais">
            </div>
        </section>

        <section class="depoimentos" id="depoimentos">
            <h2>O que dizem nossos clientes</h2>
            
            <div class="depoimentos-container" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin-bottom: 40px;">
                <?php if (count($depoimentos) > 0): ?>
                    <?php foreach ($depoimentos as $d): ?>
                        <div class="depoimento-card" style="flex: 1; min-width: 280px; max-width: 350px;">
                            <div class="estrelas">
                                <?= str_repeat('★', $d['estrelas']) ?><?= str_repeat('☆', 5 - $d['estrelas']) ?>
                            </div>
                            <p>"<?= htmlspecialchars($d['texto']) ?>"</p>
                            <div class="autor">- <?= htmlspecialchars($d['nome']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; font-style: italic;">Seja o primeiro a avaliar nosso trabalho logo abaixo!</p>
                <?php endif; ?>
            </div>

            <div style="max-width: 500px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: left;">
                <h3 style="font-family: var(--fonte-titulos); color: var(--cor-primaria); margin-bottom: 15px; text-align: center;">Deixe sua Avaliação</h3>
                
                <?php if (!empty($msg_feedback)): ?>
                    <p style="background: #e1f5fe; color: #0288d1; padding: 10px; border-radius: 4px; font-size: 0.9rem; margin-bottom: 15px; text-align: center;"><?= $msg_feedback ?></p>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/index.php#depoimentos" method="POST">
                    <input type="hidden" name="enviar_feedback" value="1">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight: 500; margin-bottom: 5px;">Seu Nome:</label>
                        <input type="text" name="nome_cliente" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight: 500; margin-bottom: 5px;">Nota (Estrelas):</label>
                        <select name="estrelas" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="5">⭐⭐⭐⭐⭐ (Excelente)</option>
                            <option value="4">⭐⭐⭐⭐ (Muito Bom)</option>
                            <option value="3">⭐⭐⭐ (Bom)</option>
                            <option value="2">⭐⭐ (Regular)</option>
                            <option value="1">⭐ (Ruim)</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-weight: 500; margin-bottom: 5px;">Sua Mensagem:</label>
                        <textarea name="texto_cliente" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
                    </div>

                    <button type="submit" style="background: var(--cor-primaria); color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer; width: 100%;">Enviar Depoimento</button>
                </form>
            </div>
        </section>

        <section id="contato" class="contato">
            <h2>Entre em Contato</h2>
            <div class="contato-info">
                <p><i class="fab fa-whatsapp"></i> <strong>WhatsApp:</strong> <?= htmlspecialchars($config_loja['whatsapp']) ?></p>
                <p><i class="fab fa-instagram"></i> <strong>Instagram:</strong> <?= htmlspecialchars($config_loja['instagram']) ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Endereço:</strong> <?= htmlspecialchars($config_loja['endereco']) ?></p>
            </div>
        </section>
    </main>

    <footer>
        <p><strong><?= htmlspecialchars($config_loja['nome_loja']) ?></strong> - Velas artesanais</p>
        <p>&copy; 2026 Todos os direitos reservados</p>
    </footer>

    <a href="https://wa.me/<?= $config_loja['whatsapp'] ?>?text=Olá! Estava navegando no site e gostaria de conhecer mais sobre as velas." 
       target="_blank" 
       class="whatsapp-flutuante" 
       aria-label="Fale conosco pelo WhatsApp">
        <span class="icone-whats">💬</span>
        <span class="texto-whats">Fale Conosco</span>
    </a>

    <script src="<?= BASE_URL ?>/script.js"></script>
</body>
</html>