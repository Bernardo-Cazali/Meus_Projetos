<?php
session_start();
include_once("conexao.php");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EletroCenter</title>

    <link rel="stylesheet" href="style.css">

    <link rel="stylesheet" href="inicio.css">
</head>

<body onload="slide()">

<div class="container">

    <header class="topo">

        <div class="logo-area">

            <img src="imagem/r2.png" class="logo">

            <h1>EletroCenter</h1>

        </div>

        <div class="pesquisa">

            <form action="#" method="get">

                <input
                    type="text"
                    placeholder="Pesquisar produto..."
                    name="produto">

            </form>

        </div>

        <div class="acoes">

            <a href="carrinho.php">
                <img src="imagem/ss.png" width="50px">
            </a>

            <?php

            if(isset($_SESSION['nome']))
            {
                echo '<a href="encerrar.php" class="login-btn">
                      '.$_SESSION['nome'].' (Sair)
                      </a>';
            }
            else
            {
                echo '<a href="login.php">
                      <img src="imagem/login.png" width="45px">
                      </a>';
            }

            ?>

        </div>

    </header>

    <section class="banner">

        <img src="imagem/gel1.jpg" id="banner">

    </section>

    <section class="todos-produtos">

    <?php

    if(isset($_GET['produto']))
        $produto = $_GET['produto'];
    else
        $produto = '';

    $sql = "SELECT * FROM produtos
            WHERE nome LIKE '%$produto%'
            ORDER BY rand()";

    $result = mysqli_query($conn, $sql);

    while($row = $result->fetch_assoc())
    {
        echo '<div class="produto-card">';

        echo '<h3>'.$row['nome'].'</h3>';

        echo '<img src="img/'.$row['imagem'].'" class="produto-img">';

        echo '<p class="preco">
              R$ '.number_format($row['preco'], 2, ',', '.').'
              </p>';

        echo '<div class="botoes">';

        echo '<a href="carrinho.php?acao=add&id='.$row['id'].'">

              <img src="imagem/mais.png" width="35px">

              </a>';

        echo '</div>';

        echo '</div>';
    }

    $conn->close();

    ?>

    </section>

    <footer class="rodape">

        <h3>Fique ligado nas redes sociais!</h3>

        <div class="redes">

            <img src="imagem/facebook.png" width="40px">

            <img src="imagem/instagram.png" width="40px">

            <img src="imagem/twiter.png" width="40px">

        </div>

    </footer>

</div>

<script src="js.js"></script>

</body>
</html>